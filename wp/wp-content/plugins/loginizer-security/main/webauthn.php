<?php

namespace LoginizerPro;

if(!defined('ABSPATH')){
	die('Hacking Attempt');
}

/**
 * WebAuthn Exception
 */
class WebAuthnException extends \Exception {
	const INVALID_DATA = 1;
	const INVALID_TYPE = 2;
	const INVALID_CHALLENGE = 3;
	const INVALID_ORIGIN = 4;
	const INVALID_RELYING_PARTY = 5;
	const INVALID_SIGNATURE = 6;
	const INVALID_PUBLIC_KEY = 7;
	const USER_PRESENT = 8;
	const USER_VERIFICATED = 9;
	const SIGNATURE_COUNTER = 10;
	const CBOR = 11;
	const BYTEBUFFER = 12;
}

/**
 * ByteBuffer helper class
 * Stores binary data and serializes to RFC 1342 / MIME format for JavaScript client consumption
 */
class ByteBuffer implements \JsonSerializable {

	/**
	 * @var string Binary data
	 */
	private $data;

	/**
	 * @param string|ByteBuffer $data
	 */
	public function __construct($data){
		if($data instanceof self){
			$this->data = $data->getBinaryString();
		}else{
			$this->data = (string) $data;
		}
	}

	/**
	 * Return raw binary string
	 * @return string
	 */
	public function getBinaryString(): string {
		return $this->data;
	}

	/**
	 * Return length in bytes
	 * @return int
	 */
	public function getLength(): int {
		return strlen($this->data);
	}

	/**
	 * Serializes to JSON. Uses the MIME-encoded binary format compatible with passkey.js
	 * @return string
	 */
	public function jsonSerialize(): string {
		return '=?BINARY?B?' . \base64_encode($this->data) . '?=';
	}

	/**
	 * String representation
	 * @return string
	 */
	public function __toString(): string {
		return $this->data;
	}

	/**
	 * Compares two buffers
	 * @param mixed $buffer
	 * @return bool
	 */
	public function equals($buffer): bool {
		if($buffer instanceof self){
			return hash_equals($this->data, $buffer->getBinaryString());
		}
		if(is_string($buffer)){
			return hash_equals($this->data, $buffer);
		}
		return false;
	}
}

/**
 * Native lightweight WebAuthn implementation for Loginizer Pro
 * Zero external dependencies. Uses PHP OpenSSL and native CBOR decoding.
 */
class WebAuthn {

	/**
	 * Relying Party Name
	 * @var string
	 */
	private $rpName;

	/**
	 * Relying Party ID (Domain)
	 * @var string
	 */
	private $rpId;

	/**
	 * SHA-256 of RP ID
	 * @var string
	 */
	private $rpIdHash;

	/**
	 * Generated challenge
	 * @var ByteBuffer|null
	 */
	private $challenge;

	/**
	 * Signature counter from authenticator
	 * @var int|null
	 */
	private $signatureCounter;

	/**
	 * Supported COSE algorithms
	 */
	const COSE_ALG_ES256 = -7;   // ECDSA w/ SHA-256 over P-256
	const COSE_ALG_RS256 = -257; // RSASSA-PKCS1-v1_5 w/ SHA-256
	const COSE_ALG_EDDSA = -8;   // EdDSA over Ed25519

	/**
	 * COSE Key Types
	 */
	const COSE_KTY_OKP = 1;
	const COSE_KTY_EC2 = 2;
	const COSE_KTY_RSA = 3;

	/**
	 * Constructor
	 * @param string $rpName
	 * @param string $rpId
	 * @param array|null $allowedFormats
	 * @throws WebAuthnException
	 */
	public function __construct($rpName, $rpId, $allowedFormats = null){
		if(!function_exists('openssl_verify')){
			throw new WebAuthnException('OpenSSL module is required for WebAuthn', WebAuthnException::INVALID_DATA);
		}

		$this->rpName = (string) $rpName;
		$this->rpId = (string) $rpId;
		$this->rpIdHash = hash('sha256', $this->rpId, true);
		$this->challenge = null;
		$this->signatureCounter = null;
	}

	/**
	 * Generates or returns existing challenge buffer
	 * @param int $length
	 * @return ByteBuffer
	 */
	public function getChallenge($length = 32): ByteBuffer {
		if(!$this->challenge){
			$this->challenge = new ByteBuffer(random_bytes($length));
		}
		return $this->challenge;
	}

	/**
	 * Returns current signature counter
	 * @return int|null
	 */
	public function getSignatureCounter(){
		return is_int($this->signatureCounter) ? $this->signatureCounter : null;
	}

	/**
	 * Builds creation arguments for navigator.credentials.create()
	 * @param int|string $userId
	 * @param string $userName
	 * @param string $userDisplayName
	 * @param int $timeout
	 * @param bool|string $requireResidentKey
	 * @param bool|string $requireUserVerification
	 * @param bool|null $crossPlatformAttachment
	 * @param array|null $excludeCredentialIds
	 * @return \stdClass
	 */
	public function getCreateArgs(
		$userId,
		$userName,
		$userDisplayName,
		$timeout = 30,
		$requireResidentKey = true,
		$requireUserVerification = 'preferred',
		$crossPlatformAttachment = null,
		$excludeCredentialIds = []
	): \stdClass {

		$args = new \stdClass();
		$args->publicKey = new \stdClass();

		// Relying Party
		$args->publicKey->rp = new \stdClass();
		$args->publicKey->rp->name = $this->rpName;
		$args->publicKey->rp->id = $this->rpId;

		// Authenticator Selection
		$args->publicKey->authenticatorSelection = new \stdClass();

		// User Verification
		if(is_bool($requireUserVerification)){
			$args->publicKey->authenticatorSelection->userVerification = $requireUserVerification ? 'required' : 'preferred';
		}else if(is_string($requireUserVerification) && in_array(strtolower($requireUserVerification), ['required', 'preferred', 'discouraged'], true)){
			$args->publicKey->authenticatorSelection->userVerification = strtolower($requireUserVerification);
		}else{
			$args->publicKey->authenticatorSelection->userVerification = 'preferred';
		}

		// Resident Key
		if(is_bool($requireResidentKey) && $requireResidentKey){
			$args->publicKey->authenticatorSelection->requireResidentKey = true;
			$args->publicKey->authenticatorSelection->residentKey = 'required';
		}else if(is_string($requireResidentKey) && in_array(strtolower($requireResidentKey), ['required', 'preferred', 'discouraged'], true)){
			$rk = strtolower($requireResidentKey);
			$args->publicKey->authenticatorSelection->residentKey = $rk;
			$args->publicKey->authenticatorSelection->requireResidentKey = ($rk === 'required');
		}else{
			$args->publicKey->authenticatorSelection->requireResidentKey = false;
			$args->publicKey->authenticatorSelection->residentKey = 'preferred';
		}

		// Authenticator Attachment
		if(is_bool($crossPlatformAttachment)){
			$args->publicKey->authenticatorSelection->authenticatorAttachment = $crossPlatformAttachment ? 'cross-platform' : 'platform';
		}

		// User entity
		$args->publicKey->user = new \stdClass();
		$args->publicKey->user->id = new ByteBuffer((string) $userId);
		$args->publicKey->user->name = (string) $userName;
		$args->publicKey->user->displayName = (string) $userDisplayName;

		// Supported Algorithms (ES256, RS256)
		$args->publicKey->pubKeyCredParams = [
			(object) ['type' => 'public-key', 'alg' => self::COSE_ALG_ES256],
			(object) ['type' => 'public-key', 'alg' => self::COSE_ALG_RS256]
		];

		$args->publicKey->attestation = 'none';
		$args->publicKey->timeout = ((int) $timeout > 0 ? (int) $timeout : 30) * 1000;
		$args->publicKey->challenge = $this->getChallenge();

		// Exclude already registered credentials
		$args->publicKey->excludeCredentials = [];
		if(!empty($excludeCredentialIds) && is_array($excludeCredentialIds)){
			foreach($excludeCredentialIds as $id){
				$item = new \stdClass();
				$item->id = ($id instanceof ByteBuffer) ? $id : new ByteBuffer($id);
				$item->type = 'public-key';
				$item->transports = ['usb', 'nfc', 'ble', 'hybrid', 'internal'];
				$args->publicKey->excludeCredentials[] = $item;
			}
		}

		return $args;
	}

	/**
	 * Builds request arguments for navigator.credentials.get()
	 * @param array $credentialIds
	 * @param int $timeout
	 * @param bool $allowUsb
	 * @param bool $allowNfc
	 * @param bool $allowBle
	 * @param bool $allowHybrid
	 * @param bool $allowInternal
	 * @param bool|string $requireUserVerification
	 * @return \stdClass
	 */
	public function getGetArgs(
		$credentialIds = [],
		$timeout = 30,
		$allowUsb = false,
		$allowNfc = false,
		$allowBle = false,
		$allowHybrid = true,
		$allowInternal = true,
		$requireUserVerification = 'preferred'
	): \stdClass {

		if(is_bool($requireUserVerification)){
			$requireUserVerification = $requireUserVerification ? 'required' : 'preferred';
		}else if(is_string($requireUserVerification) && in_array(strtolower($requireUserVerification), ['required', 'preferred', 'discouraged'], true)){
			$requireUserVerification = strtolower($requireUserVerification);
		}else{
			$requireUserVerification = 'preferred';
		}

		$args = new \stdClass();
		$args->publicKey = new \stdClass();
		$args->publicKey->timeout = ((int) $timeout > 0 ? (int) $timeout : 30) * 1000;
		$args->publicKey->challenge = $this->getChallenge();
		$args->publicKey->userVerification = $requireUserVerification;
		$args->publicKey->rpId = $this->rpId;

		if(!empty($credentialIds) && is_array($credentialIds)){
			$args->publicKey->allowCredentials = [];
			foreach($credentialIds as $id){
				$item = new \stdClass();
				$item->id = ($id instanceof ByteBuffer) ? $id : new ByteBuffer($id);
				$item->type = 'public-key';
				$item->transports = [];
				if($allowUsb) $item->transports[] = 'usb';
				if($allowNfc) $item->transports[] = 'nfc';
				if($allowBle) $item->transports[] = 'ble';
				if($allowHybrid) $item->transports[] = 'hybrid';
				if($allowInternal) $item->transports[] = 'internal';
				$args->publicKey->allowCredentials[] = $item;
			}
		}

		return $args;
	}

	/**
	 * Validates credential creation response and returns public key credentials
	 * @param string $clientDataJSON Binary clientDataJSON
	 * @param string $attestationObject Binary attestationObject
	 * @param string|ByteBuffer $challenge Binary challenge
	 * @param bool $requireUserVerification
	 * @param bool $requireUserPresent
	 * @return \stdClass
	 * @throws WebAuthnException
	 */
	public function processCreate(
		$clientDataJSON,
		$attestationObject,
		$challenge,
		$requireUserVerification = false,
		$requireUserPresent = true
	): \stdClass {

		// 1. Verify clientDataJSON
		$clientData = json_decode($clientDataJSON);
		if(!is_object($clientData)){
			throw new WebAuthnException('Invalid client data JSON', WebAuthnException::INVALID_DATA);
		}

		if(!isset($clientData->type) || $clientData->type !== 'webauthn.create'){
			throw new WebAuthnException('Invalid client data type: ' . ($clientData->type ?? ''), WebAuthnException::INVALID_TYPE);
		}

		$expectedChallenge = ($challenge instanceof ByteBuffer) ? $challenge->getBinaryString() : (string) $challenge;
		$receivedChallenge = self::base64UrlDecode($clientData->challenge ?? '');
		if(!hash_equals($expectedChallenge, $receivedChallenge)){
			throw new WebAuthnException('Invalid challenge', WebAuthnException::INVALID_CHALLENGE);
		}

		if(!isset($clientData->origin) || !$this->checkOrigin($clientData->origin)){
			throw new WebAuthnException('Invalid origin: ' . ($clientData->origin ?? ''), WebAuthnException::INVALID_ORIGIN);
		}

		// 2. Decode attestationObject (CBOR map)
		$attestation = self::cborDecode($attestationObject);
		if(!is_array($attestation) || !isset($attestation['authData']) || !is_string($attestation['authData'])){
			throw new WebAuthnException('Invalid attestation object: missing authData', WebAuthnException::INVALID_DATA);
		}

		$authData = $attestation['authData'];
		if(strlen($authData) < 37){
			throw new WebAuthnException('Authenticator data too short', WebAuthnException::INVALID_DATA);
		}

		// 3. Verify RP ID hash
		$rpIdHash = substr($authData, 0, 32);
		if(!hash_equals($this->rpIdHash, $rpIdHash)){
			throw new WebAuthnException('Invalid RP ID hash', WebAuthnException::INVALID_RELYING_PARTY);
		}

		// 4. Flags
		$flags = ord($authData[32]);
		$userPresent = ($flags & 0x01) !== 0;
		$userVerified = ($flags & 0x04) !== 0;
		$isBackupEligible = ($flags & 0x08) !== 0;
		$isBackup = ($flags & 0x10) !== 0;
		$attestedDataIncluded = ($flags & 0x40) !== 0;

		if($requireUserPresent && !$userPresent){
			throw new WebAuthnException('User not present during registration', WebAuthnException::USER_PRESENT);
		}
		if($requireUserVerification && !$userVerified){
			throw new WebAuthnException('User not verified during registration', WebAuthnException::USER_VERIFICATED);
		}
		if(!$attestedDataIncluded){
			throw new WebAuthnException('Attested credential data not present', WebAuthnException::INVALID_DATA);
		}

		// 5. Signature counter
		$signCount = unpack('N', substr($authData, 33, 4))[1];
		if($signCount > 0){
			$this->signatureCounter = $signCount;
		}

		// 6. Attested credential data (starts at byte 37)
		if(strlen($authData) < 55){
			throw new WebAuthnException('Attested credential data incomplete', WebAuthnException::INVALID_DATA);
		}

		$aaguid = substr($authData, 37, 16);
		$credIdLen = unpack('n', substr($authData, 53, 2))[1];

		if(strlen($authData) < 55 + $credIdLen){
			throw new WebAuthnException('Credential ID length exceeds authenticator data', WebAuthnException::INVALID_DATA);
		}

		$credentialId = substr($authData, 55, $credIdLen);

		// 7. Decode COSE public key (starts at byte 55 + credIdLen)
		$offset = 55 + $credIdLen;
		$coseKey = self::cborDecode($authData, $offset);
		if(!is_array($coseKey)){
			throw new WebAuthnException('Failed to decode COSE public key', WebAuthnException::INVALID_PUBLIC_KEY);
		}

		$publicKeyPem = self::coseToPem($coseKey);

		$data = new \stdClass();
		$data->credentialId = $credentialId;
		$data->credentialPublicKey = $publicKeyPem;
		$data->AAGUID = $aaguid;
		$data->signatureCounter = $this->signatureCounter;
		$data->userPresent = $userPresent;
		$data->userVerified = $userVerified;
		$data->isBackupEligible = $isBackupEligible;
		$data->isBackedUp = $isBackup;

		return $data;
	}

	/**
	 * Validates authentication response and signature
	 * @param string $clientDataJSON Binary clientDataJSON
	 * @param string $authenticatorData Binary authenticatorData
	 * @param string $signature Binary signature
	 * @param string $credentialPublicKey PEM public key
	 * @param string|ByteBuffer $challenge Binary challenge
	 * @param int|null $prevSignatureCnt
	 * @param bool $requireUserVerification
	 * @param bool $requireUserPresent
	 * @return bool
	 * @throws WebAuthnException
	 */
	public function processGet(
		$clientDataJSON,
		$authenticatorData,
		$signature,
		$credentialPublicKey,
		$challenge,
		$prevSignatureCnt = null,
		$requireUserVerification = false,
		$requireUserPresent = true
	): bool {

		// 1. Verify clientDataJSON
		$clientData = json_decode($clientDataJSON);
		if(!is_object($clientData)){
			throw new WebAuthnException('Invalid client data JSON', WebAuthnException::INVALID_DATA);
		}

		if(!isset($clientData->type) || $clientData->type !== 'webauthn.get'){
			throw new WebAuthnException('Invalid client data type: ' . ($clientData->type ?? ''), WebAuthnException::INVALID_TYPE);
		}

		// Login form embedded in another site's iframe
		if(!empty($clientData->crossOrigin)){
			throw new WebAuthnException('Cross origin request not allowed', WebAuthnException::INVALID_ORIGIN);
		}

		$expectedChallenge = ($challenge instanceof ByteBuffer) ? $challenge->getBinaryString() : (string) $challenge;
		$receivedChallenge = self::base64UrlDecode($clientData->challenge ?? '');
		if(!hash_equals($expectedChallenge, $receivedChallenge)){
			throw new WebAuthnException('Invalid challenge', WebAuthnException::INVALID_CHALLENGE);
		}

		if(!isset($clientData->origin) || !$this->checkOrigin($clientData->origin)){
			throw new WebAuthnException('Invalid origin: ' . ($clientData->origin ?? ''), WebAuthnException::INVALID_ORIGIN);
		}

		// 2. Verify authenticatorData
		if(!is_string($authenticatorData) || strlen($authenticatorData) < 37){
			throw new WebAuthnException('Invalid authenticator data', WebAuthnException::INVALID_DATA);
		}

		$rpIdHash = substr($authenticatorData, 0, 32);
		if(!hash_equals($this->rpIdHash, $rpIdHash)){
			throw new WebAuthnException('Invalid RP ID hash', WebAuthnException::INVALID_RELYING_PARTY);
		}

		$flags = ord($authenticatorData[32]);
		$userPresent = ($flags & 0x01) !== 0;
		$userVerified = ($flags & 0x04) !== 0;

		if($requireUserPresent && !$userPresent){
			throw new WebAuthnException('User not present during authentication', WebAuthnException::USER_PRESENT);
		}
		if($requireUserVerification && !$userVerified){
			throw new WebAuthnException('User not verified during authentication', WebAuthnException::USER_VERIFICATED);
		}

		$signCount = unpack('N', substr($authenticatorData, 33, 4))[1];
		if($signCount !== 0){
			$this->signatureCounter = $signCount;
		}

		// Signature counter clone detection
		if($prevSignatureCnt !== null){
			if($signCount !== 0 || $prevSignatureCnt !== 0){
				if($prevSignatureCnt >= $signCount){
					throw new WebAuthnException('Signature counter not valid (possible cloned authenticator)', WebAuthnException::SIGNATURE_COUNTER);
				}
			}
		}

		// 3. Verify signature over authenticatorData || SHA-256(clientDataJSON)
		$signedData = $authenticatorData . hash('sha256', $clientDataJSON, true);

		if(!self::verifySignature($signedData, $signature, $credentialPublicKey)){
			throw new WebAuthnException('Invalid passkey signature', WebAuthnException::INVALID_SIGNATURE);
		}

		return true;
	}

	/**
	 * Checks if the request origin matches the RP ID
	 * @param string $origin
	 * @return bool
	 */
	private function checkOrigin($origin): bool {
		if(!is_string($origin) || empty($origin)){
			return false;
		}

		$scheme = parse_url($origin, PHP_URL_SCHEME);
		$host = parse_url($origin, PHP_URL_HOST);

		if(empty($host)){
			return false;
		}

		$host = trim($host, '.');

		// Localhost can use http or https
		if($this->rpId !== 'localhost' && $scheme !== 'https'){
			return false;
		}

		// Exact match (or its www alias) only, any other subdomain (e.g. a multisite subsite) could otherwise request assertions for this RP ID and log in as its users
		return strcasecmp($host, $this->rpId) === 0 || strcasecmp($host, 'www.' . $this->rpId) === 0;
	}

	/**
	 * Verifies signature using OpenSSL or Sodium
	 * @param string $dataToVerify
	 * @param string $signature
	 * @param string $credentialPublicKey PEM string
	 * @return bool
	 * @throws WebAuthnException
	 */
	private static function verifySignature($dataToVerify, $signature, $credentialPublicKey): bool {
		// Sodium Ed25519 verification if key is EdDSA
		if(function_exists('sodium_crypto_sign_verify_detached')){
			$okpPrefix = "\x30\x2a\x30\x05\x06\x03\x2b\x65\x70\x03\x21\x00";
			if(preg_match('/BEGIN PUBLIC KEY[^-]*-----([^-]+)-----END PUBLIC KEY/s', $credentialPublicKey, $m)){
				$rawDer = base64_decode(preg_replace('/\s+/', '', $m[1]));
				if(strlen($rawDer) === 44 && substr($rawDer, 0, strlen($okpPrefix)) === $okpPrefix){
					$rawPub = substr($rawDer, strlen($okpPrefix));
					return sodium_crypto_sign_verify_detached($signature, $dataToVerify, $rawPub);
				}
			}
		}

		$pubKey = openssl_pkey_get_public($credentialPublicKey);
		if($pubKey === false){
			throw new WebAuthnException('Invalid public key format', WebAuthnException::INVALID_PUBLIC_KEY);
		}

		return (openssl_verify($dataToVerify, $signature, $pubKey, OPENSSL_ALGO_SHA256) === 1);
	}

	/**
	 * Converts a COSE public key array into SubjectPublicKeyInfo PEM format
	 * @param array $cose
	 * @return string PEM formatted key
	 * @throws WebAuthnException
	 */
	public static function coseToPem(array $cose): string {
		$kty = $cose[1] ?? null;
		$alg = $cose[3] ?? null;

		// EC2 (ECDSA P-256 / ES256)
		if($kty === self::COSE_KTY_EC2){
			$crv = $cose[-1] ?? null;
			$x = $cose[-2] ?? null;
			$y = $cose[-3] ?? null;

			if($crv !== 1 || !is_string($x) || strlen($x) !== 32 || !is_string($y) || strlen($y) !== 32){
				throw new WebAuthnException('Invalid EC2 P-256 key parameters', WebAuthnException::INVALID_PUBLIC_KEY);
			}

			$u2fKey = "\x04" . $x . $y; // 65-byte uncompressed point
			$der = self::derSequence(
				self::derSequence(
					self::derOid("\x2A\x86\x48\xCE\x3D\x02\x01") . // OID 1.2.840.10045.2.1 id-ecPublicKey
					self::derOid("\x2A\x86\x48\xCE\x3D\x03\x01\x07")  // OID 1.2.840.10045.3.1.7 prime256v1 (P-256)
				) .
				self::derBitString($u2fKey)
			);

			return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
		}

		// RSA (RSASSA-PKCS1-v1_5 / RS256)
		if($kty === self::COSE_KTY_RSA){
			$n = $cose[-1] ?? null;
			$e = $cose[-2] ?? null;

			if(!is_string($n) || !is_string($e)){
				throw new WebAuthnException('Invalid RSA key parameters', WebAuthnException::INVALID_PUBLIC_KEY);
			}

			$der = self::derSequence(
				self::derSequence(
					self::derOid("\x2A\x86\x48\x86\xF7\x0D\x01\x01\x01") . // OID 1.2.840.113549.1.1.1 rsaEncryption
					self::derNull()
				) .
				self::derBitString(
					self::derSequence(
						self::derUnsignedInteger($n) .
						self::derUnsignedInteger($e)
					)
				)
			);

			return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
		}

		// OKP (Ed25519 / EdDSA)
		if($kty === self::COSE_KTY_OKP && $alg === self::COSE_ALG_EDDSA){
			$x = $cose[-2] ?? null;
			if(!is_string($x) || strlen($x) !== 32){
				throw new WebAuthnException('Invalid Ed25519 key parameters', WebAuthnException::INVALID_PUBLIC_KEY);
			}

			$der = self::derSequence(
				self::derSequence(
					self::derOid("\x2B\x65\x70") // OID 1.3.101.112 curveEd25519
				) .
				self::derBitString($x)
			);

			return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
		}

		throw new WebAuthnException('Unsupported public key algorithm / type: kty=' . var_export($kty, true) . ', alg=' . var_export($alg, true), WebAuthnException::INVALID_PUBLIC_KEY);
	}

	/**
	 * Lightweight native CBOR Decoder
	 * Decodes one CBOR data item from binary data starting at offset
	 * @param string $data
	 * @param int $offset
	 * @return mixed
	 * @throws WebAuthnException
	 */
	public static function cborDecode($data, &$offset = 0){
		$len = strlen($data);
		if($offset >= $len){
			throw new WebAuthnException('CBOR: Unexpected end of input', WebAuthnException::CBOR);
		}

		$byte = ord($data[$offset++]);
		$type = $byte >> 5;
		$val = $byte & 0x1F;

		// Major 7: Floats / Simples
		if($type === 7){
			if($val === 20) return false;
			if($val === 21) return true;
			if($val === 22) return null;
			if($val === 24){
				$offset++;
				return null;
			}
			if($val === 25){
				$offset += 2;
				return null;
			}
			if($val === 26){
				$offset += 4;
				return null;
			}
			if($val === 27){
				$offset += 8;
				return null;
			}
			return null;
		}

		// Additional length / value
		if($val === 24){
			if($offset + 1 > $len) throw new WebAuthnException('CBOR: Truncated uint8', WebAuthnException::CBOR);
			$val = ord($data[$offset++]);
		}else if($val === 25){
			if($offset + 2 > $len) throw new WebAuthnException('CBOR: Truncated uint16', WebAuthnException::CBOR);
			$val = unpack('n', substr($data, $offset, 2))[1];
			$offset += 2;
		}else if($val === 26){
			if($offset + 4 > $len) throw new WebAuthnException('CBOR: Truncated uint32', WebAuthnException::CBOR);
			$val = unpack('N', substr($data, $offset, 4))[1];
			$offset += 4;
		}else if($val === 27){
			if($offset + 8 > $len) throw new WebAuthnException('CBOR: Truncated uint64', WebAuthnException::CBOR);
			$val = unpack('J', substr($data, $offset, 8))[1];
			$offset += 8;
		}else if($val > 27){
			throw new WebAuthnException('CBOR: Indefinite length or reserved value not supported', WebAuthnException::CBOR);
		}

		switch($type){
			case 0: // Unsigned integer
				return $val;

			case 1: // Negative integer (-1 - val)
				return -1 - $val;

			case 2: // Byte string
				if($offset + $val > $len) throw new WebAuthnException('CBOR: Truncated byte string', WebAuthnException::CBOR);
				$str = substr($data, $offset, $val);
				$offset += $val;
				return $str;

			case 3: // Text string
				if($offset + $val > $len) throw new WebAuthnException('CBOR: Truncated text string', WebAuthnException::CBOR);
				$str = substr($data, $offset, $val);
				$offset += $val;
				return $str;

			case 4: // Array
				$arr = [];
				for($i = 0; $i < $val; $i++){
					$arr[] = self::cborDecode($data, $offset);
				}
				return $arr;

			case 5: // Map
				$map = [];
				for($i = 0; $i < $val; $i++){
					$k = self::cborDecode($data, $offset);
					$v = self::cborDecode($data, $offset);
					$map[$k] = $v;
				}
				return $map;

			case 6: // Tag
				return self::cborDecode($data, $offset);
		}

		throw new WebAuthnException('CBOR: Unknown major type ' . $type, WebAuthnException::CBOR);
	}

	/**
	 * Decode Base64URL string to binary
	 * @param string $data
	 * @return string
	 */
	public static function base64UrlDecode($data): string {
		if(!is_string($data) || empty($data)){
			return '';
		}
		$b64 = strtr($data, '-_', '+/');
		$pad = strlen($b64) % 4;
		if($pad > 0){
			$b64 .= str_repeat('=', 4 - $pad);
		}
		$res = base64_decode($b64);
		return $res !== false ? $res : '';
	}

	/**
	 * Encode binary data to Base64URL
	 * @param string $data
	 * @return string
	 */
	public static function base64UrlEncode($data): string {
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	// ----------------------------------------------------
	// DER Encoding Helpers (X.690 ASN.1)
	// ----------------------------------------------------

	private static function derLength(int $len): string {
		if($len < 128){
			return chr($len);
		}
		$lenBytes = '';
		while($len > 0){
			$lenBytes = chr($len % 256) . $lenBytes;
			$len = intdiv($len, 256);
		}
		return chr(0x80 | strlen($lenBytes)) . $lenBytes;
	}

	private static function derSequence(string $contents): string {
		return "\x30" . self::derLength(strlen($contents)) . $contents;
	}

	private static function derOid(string $encoded): string {
		return "\x06" . self::derLength(strlen($encoded)) . $encoded;
	}

	private static function derBitString(string $bytes): string {
		return "\x03" . self::derLength(strlen($bytes) + 1) . "\x00" . $bytes;
	}

	private static function derNull(): string {
		return "\x05\x00";
	}

	private static function derUnsignedInteger(string $bytes): string {
		$len = strlen($bytes);
		$i = 0;
		for(; $i < ($len - 1); $i++){
			if(ord($bytes[$i]) !== 0){
				break;
			}
		}
		if($i !== 0){
			$bytes = substr($bytes, $i);
		}
		if((ord($bytes[0]) & 0x80) !== 0){
			$bytes = "\x00" . $bytes;
		}
		return "\x02" . self::derLength(strlen($bytes)) . $bytes;
	}
}
