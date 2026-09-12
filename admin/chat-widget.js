(function() {
    const threadsBox = document.getElementById('admin-chat-threads');
    const messagesBox = document.getElementById('admin-chat-messages');
    const form = document.getElementById('admin-chat-form');
    const input = document.getElementById('admin-chat-input');
    if (!threadsBox) return;

    let activeThreadId = null;
    let pollTimer = null;

    function loadThreads() {
        fetch('chat.php?action=admin_threads')
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) return;
                if (!data.threads.length) {
                    threadsBox.innerHTML = '<p class="dash-empty">No conversations yet.</p>';
                    return;
                }
                threadsBox.innerHTML = data.threads.map((thread) => `
          <button type="button" class="admin-chat-thread-btn ${thread.id === activeThreadId ? 'is-active' : ''}" data-thread-id="${thread.id}">
            <span class="admin-chat-thread-name">${thread.name}${thread.unread ? '<span class="admin-chat-unread-dot"></span>' : ''}</span>
            <span class="admin-chat-thread-sub">${thread.subtitle}</span>
            <span class="admin-chat-thread-last">${thread.lastMessage}</span>
          </button>
        `).join('');
                threadsBox.querySelectorAll('[data-thread-id]').forEach((button) => {
                    button.addEventListener('click', () => openThread(parseInt(button.dataset.threadId, 10)));
                });
            });
    }

    function openThread(threadId) {
        activeThreadId = threadId;
        form.hidden = false;
        fetch('chat.php?action=admin_thread_messages&thread_id=' + threadId)
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) return;
                renderMessages(data.messages);
                loadThreads();
            });
    }

    function renderMessages(messages) {
        if (!messages.length) {
            messagesBox.innerHTML = '<p class="dash-empty">No messages yet.</p>';
            return;
        }
        messagesBox.innerHTML = messages.map((message) => `
      <div class="admin-chat-bubble admin-chat-bubble--${message.sender}">
        <p>${message.message}</p>
        <span>${message.time}</span>
      </div>
    `).join('');
        messagesBox.scrollTop = messagesBox.scrollHeight;
    }

    if (form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const message = input.value.trim();
            if (!message || !activeThreadId) return;
            const formData = new FormData();
            formData.append('action', 'admin_reply');
            formData.append('thread_id', activeThreadId);
            formData.append('message', message);
            fetch('chat.php', { method: 'POST', body: formData })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        input.value = '';
                        openThread(activeThreadId);
                    }
                });
        });
    }

    loadThreads();
    pollTimer = setInterval(() => {
        loadThreads();
        if (activeThreadId) {
            fetch('chat.php?action=admin_thread_messages&thread_id=' + activeThreadId)
                .then((response) => response.json())
                .then((data) => { if (data.success) renderMessages(data.messages); });
        }
    }, 5000);
})();