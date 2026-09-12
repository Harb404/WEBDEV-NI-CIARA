document.addEventListener('DOMContentLoaded', function() {

    /* ============ MOBILE NAV ============ */
    var hamburger = document.getElementById('hamburger');
    var mobileNav = document.getElementById('mobileNav');
    if (hamburger && mobileNav) {
        hamburger.addEventListener('click', function() {
            var isOpen = mobileNav.classList.toggle('is-open');
            hamburger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    /* ============ DROPDOWNS (tap support for touch devices) ============ */
    document.querySelectorAll('.has-dropdown > button').forEach(function(button) {
        button.addEventListener('click', function() {
            var parent = button.closest('.has-dropdown');
            var isOpen = parent.classList.toggle('is-open');
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            var dropdown = parent.querySelector('.dropdown');
            if (dropdown) {
                dropdown.style.opacity = isOpen ? '1' : '';
                dropdown.style.visibility = isOpen ? 'visible' : '';
                dropdown.style.transform = isOpen ? 'translateY(0)' : '';
            }
        });
    });

    /* ============ COMMITTEE CAROUSEL ============ */
    var carousel = document.querySelector('.carousel');
    if (carousel) {
        var committees = [];
        try { committees = JSON.parse(carousel.dataset.committees || '[]'); } catch (e) { committees = []; }

        var track = carousel.querySelector('.carousel-track');
        var prevBtn = carousel.querySelector('.carousel-arrow--prev');
        var nextBtn = carousel.querySelector('.carousel-arrow--next');
        var titleEl = document.getElementById('carouselTitle');
        var descEl = document.getElementById('carouselDesc');
        var activeIndex = 0;

        function renderSlide(role, index) {
            var slide = track.querySelector('[data-role="' + role + '"]');
            if (!slide || !committees.length) return;
            var item = committees[(index + committees.length) % committees.length];
            var img = slide.querySelector('img');
            var label = slide.querySelector('.carousel-slide-label');
            if (img) { img.src = item.image;
                img.alt = item.title; }
            if (label) { label.textContent = item.title; }
            slide.dataset.categoryKey = item.key;
        }

        function renderCarousel() {
            if (!committees.length) return;
            renderSlide('prev', activeIndex - 1);
            renderSlide('center', activeIndex);
            renderSlide('next', activeIndex + 1);
            var current = committees[activeIndex];
            if (titleEl) titleEl.textContent = current.title;
            if (descEl) descEl.textContent = current.desc;
        }

        function goToCategory(key) {
            var index = committees.findIndex(function(c) { return c.key === key; });
            if (index !== -1) { activeIndex = index;
                renderCarousel(); }
        }

        if (prevBtn) prevBtn.addEventListener('click', function() { activeIndex = (activeIndex - 1 + committees.length) % committees.length;
            renderCarousel(); });
        if (nextBtn) nextBtn.addEventListener('click', function() { activeIndex = (activeIndex + 1) % committees.length;
            renderCarousel(); });

        track.querySelectorAll('[data-role="prev"], [data-role="next"]').forEach(function(slide) {
            slide.addEventListener('click', function() {
                if (slide.dataset.role === 'prev') { activeIndex = (activeIndex - 1 + committees.length) % committees.length; } else { activeIndex = (activeIndex + 1) % committees.length; }
                renderCarousel();
            });
        });

        document.querySelectorAll('[data-category-link]').forEach(function(link) {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                goToCategory(link.dataset.categoryLink);
                filterPostingsByCategory(link.dataset.categoryLink);
                document.getElementById('committees').scrollIntoView({ behavior: 'smooth' });
            });
        });

        renderCarousel();
    }

    /* ============ FILTER POSTINGS BY CATEGORY (from committees dropdown) ============ */
    function filterPostingsByCategory(categoryKey) {
        document.querySelectorAll('.posting-card').forEach(function(card) {
            card.style.display = (card.dataset.category === categoryKey) ? '' : 'none';
        });
    }

    /* ============ APPLY / WITHDRAW (AJAX) ============ */
    document.querySelectorAll('[data-application-form]').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            var formData = new FormData(form);
            var card = form.closest('.posting-card');
            fetch('applications.php', { method: 'POST', body: formData })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.requiresLogin) {
                        window.location.href = 'login.php?next=' + encodeURIComponent('index.php%23committees');
                        return;
                    }
                    if (data.success && card && data.postingHtml !== undefined) {
                        var actionsWrap = card.querySelector('[data-application-form]').parentElement;
                        var existingForms = card.querySelectorAll('form, .application-status');
                        existingForms.forEach(function(el) { el.remove(); });
                        card.insertAdjacentHTML('beforeend', data.postingHtml);
                        attachApplicationFormHandlers(card);
                    }
                    showToast(data.message || (data.success ? 'Done.' : 'Something went wrong.'));
                })
                .catch(function() { showToast('Network error — please try again.'); });
        });
    });

    function attachApplicationFormHandlers(scope) {
        scope.querySelectorAll('[data-application-form]').forEach(function(form) {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                var formData = new FormData(form);
                fetch('applications.php', { method: 'POST', body: formData })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.requiresLogin) { window.location.href = 'login.php'; return; }
                        if (data.success && data.postingHtml !== undefined) {
                            scope.querySelectorAll('form, .application-status').forEach(function(el) { el.remove(); });
                            scope.insertAdjacentHTML('beforeend', data.postingHtml);
                            attachApplicationFormHandlers(scope);
                        }
                        showToast(data.message || '');
                    });
            });
        });
    }

    function showToast(text) {
        if (!text) return;
        var toast = document.createElement('div');
        toast.className = 'toast-message';
        toast.textContent = text;
        toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#211d54;color:#fff;padding:12px 22px;border-radius:999px;font-size:13.5px;font-weight:600;z-index:999;box-shadow:0 10px 24px rgba(0,0,0,0.2);';
        document.body.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 3200);
    }

    /* ============ NOTIFICATIONS ============ */
    var notifToggle = document.getElementById('notif-toggle-btn');
    var notifPanel = document.getElementById('notif-panel');
    var notifCount = document.getElementById('notif-count');
    var notifList = document.getElementById('notif-list');

    function renderNotifications(data) {
        if (!notifList) return;
        if (!data.notifications || !data.notifications.length) {
            notifList.innerHTML = '<p class="notif-empty">Nothing new yet.</p>';
        } else {
            notifList.innerHTML = data.notifications.map(function(n) {
                return '<div class="notif-item' + (n.unread ? ' notif-item--unread' : '') + '">' + n.text + '<time>' + n.time + '</time></div>';
            }).join('');
        }
        if (notifCount) {
            if (data.unreadCount > 0) { notifCount.style.display = 'flex';
                notifCount.textContent = data.unreadCount; } else { notifCount.style.display = 'none'; }
        }
    }

    function pollNotifications() {
        if (!notifToggle) return;
        fetch('notification.php?action=poll')
            .then(function(response) { return response.json(); })
            .then(function(data) { if (data.success) renderNotifications(data); })
            .catch(function() {});
    }

    if (notifToggle && notifPanel) {
        notifToggle.addEventListener('click', function() {
            var isOpen = notifPanel.classList.toggle('is-open');
            notifToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            if (isOpen) {
                fetch('notification.php?action=mark_read', { method: 'POST' })
                    .then(function(response) { return response.json(); })
                    .then(function(data) { if (data.success) renderNotifications(data); });
            }
        });
        document.addEventListener('click', function(event) {
            if (!notifPanel.contains(event.target) && !notifToggle.contains(event.target)) {
                notifPanel.classList.remove('is-open');
            }
        });
        pollNotifications();
        setInterval(pollNotifications, 15000);
    }

    /* ============ PASSWORD SHOW/HIDE ============ */
    document.querySelectorAll('.password-toggle').forEach(function(button) {
        button.addEventListener('click', function() {
            var input = document.getElementById(button.dataset.toggleFor);
            if (!input) return;
            var showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            button.setAttribute('aria-pressed', showing ? 'false' : 'true');
        });
    });

    /* ============ REGISTER: role toggle + password match ============ */
    var roleButtons = document.querySelectorAll('[data-role-btn]');
    var roleInput = document.getElementById('register-role');
    if (roleButtons.length && roleInput) {
        roleButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                roleButtons.forEach(function(b) { b.classList.remove('is-active'); });
                button.classList.add('is-active');
                roleInput.value = button.dataset.roleBtn;
                document.querySelectorAll('[data-role-field]').forEach(function(field) {
                    field.hidden = field.dataset.roleField !== button.dataset.roleBtn;
                });
            });
        });
    }

    var registerPassword = document.getElementById('register-password');
    var confirmPassword = document.getElementById('register-confirm-password');
    var confirmError = document.getElementById('confirm-password-error');
    if (registerPassword && confirmPassword && confirmError) {
        function checkMatch() {
            var mismatch = confirmPassword.value.length > 0 && confirmPassword.value !== registerPassword.value;
            confirmError.hidden = !mismatch;
        }
        registerPassword.addEventListener('input', checkMatch);
        confirmPassword.addEventListener('input', checkMatch);
    }

    /* ============ LIVE CHAT (contact page) ============ */
    var chatForm = document.getElementById('chat-form');
    var chatMessages = document.getElementById('chat-messages');
    if (chatForm && chatMessages) {
        var nameInput = document.getElementById('chat-name-input');
        var messageInput = document.getElementById('chat-message-input');

        function renderChat(messages) {
            if (!messages.length) {
                chatMessages.innerHTML = '<p class="chat-empty">Say hello — we\'re happy to help.</p>';
                return;
            }
            chatMessages.innerHTML = messages.map(function(m) {
                return '<div class="chat-bubble chat-bubble--' + m.sender + '"><p>' + m.message + '</p><span>' + m.time + '</span></div>';
            }).join('');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function pollChat() {
            fetch('chat.php?action=poll')
                .then(function(response) { return response.json(); })
                .then(function(data) { if (data.success && data.hasThread) renderChat(data.messages); });
        }

        chatForm.addEventListener('submit', function(event) {
            event.preventDefault();
            var message = messageInput.value.trim();
            if (!message) return;
            var formData = new FormData();
            formData.append('action', 'send');
            formData.append('message', message);
            if (nameInput) formData.append('name', nameInput.value.trim());
            fetch('chat.php', { method: 'POST', body: formData })
                .then(function(response) { return response.json(); })
                .then(function(data) { if (data.success) { messageInput.value = '';
                        pollChat(); } });
        });

        pollChat();
        setInterval(pollChat, 5000);
    }

});