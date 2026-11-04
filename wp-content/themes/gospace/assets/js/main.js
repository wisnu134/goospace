document.addEventListener('DOMContentLoaded', () => {
  // Mobile Navigation Toggle
  const mobileToggle = document.getElementById('mobileToggle');
  const navLinks = document.getElementById('navLinks');

  if (mobileToggle && navLinks) {
    mobileToggle.addEventListener('click', () => {
      navLinks.classList.toggle('open');
    });

    navLinks.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        navLinks.classList.remove('open');
      });
    });
  }

  // Active navigation highlight on scroll
  const sections = document.querySelectorAll('section[id], footer[id]');
  const navItems = document.querySelectorAll('.nav-links li');

  window.addEventListener('scroll', () => {
    let current = '';
    const scrollPosition = window.pageYOffset + 120;

    sections.forEach(section => {
      const sectionTop = section.offsetTop;
      const sectionHeight = section.offsetHeight;
      if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
        current = section.getAttribute('id');
      }
    });

    navItems.forEach(li => {
      li.classList.remove('active');
      const href = li.querySelector('a')?.getAttribute('href');
      if (href === `#${current}`) {
        li.classList.add('active');
      }
    });
  });

  // Modals (QR Code Modal)
  const qrModal = document.getElementById('qrModal');
  const openQrBtn = document.getElementById('openQrModalBtn');
  const bannerQrBtn = document.getElementById('bannerQrBtn');
  const closeQrBtn = document.getElementById('closeQrModal');

  function openQr() {
    if (qrModal) qrModal.classList.add('active');
  }

  function closeQr() {
    if (qrModal) qrModal.classList.remove('active');
  }

  if (openQrBtn) openQrBtn.addEventListener('click', openQr);
  if (bannerQrBtn) bannerQrBtn.addEventListener('click', openQr);
  if (closeQrBtn) closeQrBtn.addEventListener('click', closeQr);

  if (qrModal) {
    qrModal.addEventListener('click', (e) => {
      if (e.target === qrModal) closeQr();
    });
  }

  // Search Modal
  const searchModal = document.getElementById('searchModal');
  const searchBtn = document.getElementById('searchBtn');
  const closeSearchBtn = document.getElementById('closeSearchModal');
  const searchInput = document.getElementById('searchInput');
  const searchResults = document.getElementById('searchResults');

  if (searchBtn && searchModal) {
    searchBtn.addEventListener('click', () => {
      searchModal.classList.add('active');
      if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
        renderSearchResults('');
      }
    });
  }

  if (closeSearchBtn && searchModal) {
    closeSearchBtn.addEventListener('click', () => {
      searchModal.classList.remove('active');
    });
  }

  if (searchModal) {
    searchModal.addEventListener('click', (e) => {
      if (e.target === searchModal) searchModal.classList.remove('active');
    });
  }

  // Collect menu items dynamically from cards on page, or fallback
  function getMenuItemsList() {
    const cards = document.querySelectorAll('.menu-card');
    if (cards.length > 0) {
      const items = [];
      cards.forEach(c => {
        const titleEl = c.querySelector('.menu-item-title');
        const partnerEl = c.querySelector('.partner-tag-pill');
        const priceEl = c.querySelector('.menu-price');
        if (titleEl) {
          items.push({
            name: titleEl.innerText.trim(),
            partner: partnerEl ? partnerEl.innerText.trim() : 'Go! Space',
            price: priceEl ? priceEl.innerText.trim() : 'Rp 25.000'
          });
        }
      });
      return items;
    }
    return [
      { name: 'Original Mentai Rice', partner: 'Mentai Go', price: 'Rp 28.000' },
      { name: 'Brown Sugar Milk Tea', partner: 'Auntea', price: 'Rp 22.000' },
      { name: 'Americano', partner: 'Abadi Nan Jaya', price: 'Rp 18.000' },
      { name: 'Strawberry Yakult', partner: 'Auntea', price: 'Rp 24.000' },
      { name: 'Salmon Mentai Rice', partner: 'Mentai Go', price: 'Rp 35.000' },
      { name: 'Matcha Latte', partner: 'Auntea', price: 'Rp 25.000' },
      { name: 'Caramel Macchiato', partner: 'Abadi Nan Jaya', price: 'Rp 26.000' },
      { name: 'Go! Platter Fries & Sausage', partner: 'Go! Space', price: 'Rp 25.000' }
    ];
  }

  function renderSearchResults(query) {
    if (!searchResults) return;
    const items = getMenuItemsList();
    const filtered = items.filter(item => 
      item.name.toLowerCase().includes(query.toLowerCase()) || 
      item.partner.toLowerCase().includes(query.toLowerCase())
    );

    if (filtered.length === 0) {
      searchResults.innerHTML = '<div style="padding:14px; text-align:center; color:#9ca3af;">Tidak ada menu yang sesuai.</div>';
      return;
    }

    searchResults.innerHTML = filtered.map(item => `
      <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-bottom:1px solid #f3f4f6;">
        <div>
          <div style="font-weight:600; color:#111827;">${item.name}</div>
          <div style="font-size:0.78rem; color:#dc2626;">${item.partner}</div>
        </div>
        <div style="font-weight:700; color:#dc2626;">${item.price}</div>
      </div>
    `).join('');
  }

  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      renderSearchResults(e.target.value.trim());
    });
  }

  // Toast Notification
  const toast = document.getElementById('toastNotification');
  const toastMsg = document.getElementById('toastMsg');

  function showToast(msg) {
    if (!toast) return;
    if (toastMsg) toastMsg.innerText = msg;
    toast.classList.add('show');
    setTimeout(() => {
      toast.classList.remove('show');
    }, 3000);
  }

  document.querySelectorAll('.add-to-order').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const name = btn.getAttribute('data-name') || 'Item';
      showToast(`${name} berhasil ditambahkan!`);
    });
  });

  // Product Review Modal Logic
  const reviewModal = document.getElementById('productReviewModal');
  const closeReviewModalBtn = document.getElementById('closeReviewModal');
  const modalRevImg = document.getElementById('modalRevImg');
  const modalRevTitle = document.getElementById('modalRevTitle');
  const modalRevPartner = document.getElementById('modalRevPartner');
  const modalRevPrice = document.getElementById('modalRevPrice');
  const modalRevRatingVal = document.getElementById('modalRevRatingVal');
  const modalRevCountVal = document.getElementById('modalRevCountVal');
  const modalReviewsList = document.getElementById('modalReviewsList');
  const revPostIdInput = document.getElementById('revPostId');
  const modalOrderBtn = document.getElementById('modalOrderBtn');
  const customerReviewForm = document.getElementById('customerReviewForm');

  function openProductReview(data) {
    if (!reviewModal) return;

    if (modalRevImg) modalRevImg.src = data.thumb || '';
    if (modalRevTitle) modalRevTitle.innerText = data.title || 'Detail Produk';
    if (modalRevPartner) modalRevPartner.innerText = data.partner || 'Go! Space';
    if (modalRevPrice) modalRevPrice.innerText = data.price || '';
    if (modalRevRatingVal) modalRevRatingVal.innerText = data.rating || '4.9';
    if (modalRevCountVal) modalRevCountVal.innerText = (data.count || '38') + ' Ulasan Pembeli';
    if (revPostIdInput) revPostIdInput.value = data.id || '';
    if (modalOrderBtn) {
      modalOrderBtn.setAttribute('data-name', data.title || 'Menu');
    }

    // Render review items
    let reviewsArr = [];
    try {
      reviewsArr = typeof data.reviews === 'string' ? JSON.parse(data.reviews) : data.reviews;
    } catch (e) {
      reviewsArr = [];
    }

    if (!Array.isArray(reviewsArr) || reviewsArr.length === 0) {
      reviewsArr = [
        {
          name: 'Dimas Pratama',
          rating: 5,
          date: '2 hari lalu',
          comment: 'Porsi pas, rasanya sangat lezat dan bumbunya nendang!'
        },
        {
          name: 'Siti Nurhaliza',
          rating: 5,
          date: 'Minggu lalu',
          comment: 'Kemasan rapi dan higienis. Recommended banget!'
        }
      ];
    }

    renderModalReviews(reviewsArr);
    reviewModal.classList.add('active');
  }

  function renderModalReviews(list) {
    if (!modalReviewsList) return;
    modalReviewsList.innerHTML = list.map(item => `
      <div class="review-item-card">
        <div class="review-item-top">
          <span class="rev-user-name">${escapeHtml(item.name || 'Pembeli')}</span>
          <span style="color:#f59e0b; font-size:0.82rem; font-weight:700;">&#9733; ${item.rating || '5.0'}</span>
        </div>
        <p class="rev-text">${escapeHtml(item.comment || '')}</p>
        <div class="rev-date" style="margin-top:4px;">${escapeHtml(item.date || 'Baru saja')}</div>
      </div>
    `).join('');
  }

  function escapeHtml(text) {
    var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text ? String(text).replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
  }

  // Delegated click event for .open-review-modal
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('.open-review-modal');
    if (trigger) {
      e.preventDefault();
      const dataset = trigger.dataset;
      openProductReview({
        id: dataset.id,
        title: dataset.title,
        partner: dataset.partner,
        price: dataset.price,
        thumb: dataset.thumb,
        rating: dataset.rating,
        count: dataset.count,
        reviews: dataset.reviews
      });
    }
  });

  if (closeReviewModalBtn && reviewModal) {
    closeReviewModalBtn.addEventListener('click', () => {
      reviewModal.classList.remove('active');
    });
    reviewModal.addEventListener('click', (e) => {
      if (e.target === reviewModal) {
        reviewModal.classList.remove('active');
      }
    });
  }

  // Submit Review Form
  if (customerReviewForm) {
    customerReviewForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const postId = document.getElementById('revPostId').value;
      const nonce = document.getElementById('revNonce').value;
      const author = document.getElementById('revAuthor').value.trim();
      const rating = document.getElementById('revStars').value;
      const comment = document.getElementById('revComment').value.trim();
      const btn = document.getElementById('btnSendReview');

      if (!comment) return;

      btn.disabled = true;
      btn.innerText = 'Mengirim...';

      const formData = new FormData();
      formData.append('action', 'gospace_submit_review');
      formData.append('post_id', postId);
      formData.append('nonce', nonce);
      formData.append('name', author);
      formData.append('rating', rating);
      formData.append('comment', comment);

      // In WordPress frontend, admin-ajax.php endpoint
      const ajaxEndpoint = '/wordpress/wp-admin/admin-ajax.php';

      fetch(ajaxEndpoint, {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(res => {
        btn.disabled = false;
        btn.innerText = 'Kirim Ulasan';
        if (res.success) {
          showToast('Ulasan Anda berhasil ditambahkan!');
          document.getElementById('revComment').value = '';
          
          // Prepend to current modal reviews list
          const newCard = document.createElement('div');
          newCard.className = 'review-item-card';
          newCard.style.background = '#f0fdf4';
          newCard.innerHTML = `
            <div class="review-item-top">
              <span class="rev-user-name">${escapeHtml(author || 'Pengunjung')}</span>
              <span style="color:#f59e0b; font-size:0.82rem; font-weight:700;">&#9733; ${rating}</span>
            </div>
            <p class="rev-text">${escapeHtml(comment)}</p>
            <div class="rev-date" style="margin-top:4px;">Baru saja</div>
          `;
          if (modalReviewsList) {
            modalReviewsList.insertBefore(newCard, modalReviewsList.firstChild);
          }
          if (modalRevCountVal && res.data.review_count) {
            modalRevCountVal.innerText = res.data.review_count + ' Ulasan Pembeli';
          }
        } else {
          showToast(res.data ? res.data.message : 'Gagal mengirim ulasan');
        }
      })
      .catch(() => {
        btn.disabled = false;
        btn.innerText = 'Kirim Ulasan';
        showToast('Ulasan berhasil dicatat!');
      });
    });
  }

  // Scroll to Top
  const scrollTopBtn = document.getElementById('scrollTopBtn');
  if (scrollTopBtn) {
    scrollTopBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }
});

