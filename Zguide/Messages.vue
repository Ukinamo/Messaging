<template>
  <div class="messages-page">
    <!-- Messages Layout: Conversation List + Chat Thread -->
    <div class="messages-layout">
      <!-- Conversation List (Left Panel) -->
      <aside class="conversations-panel">
        <!-- Search and Filters -->
        <div class="conversations-header">
          <div class="search-wrapper">
            <svg class="search-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input
              type="text"
              class="search-input"
              placeholder="Search conversations..."
              @input="filterConversations"
            />
          </div>

          <!-- Filter Tabs -->
          <div class="filter-tabs">
            <button
              v-for="tab in filterTabs"
              :key="tab.id"
              class="filter-tab"
              :class="{ active: activeFilter === tab.id }"
              @click="activeFilter = tab.id"
            >
              {{ tab.label }}
            </button>
          </div>
        </div>

        <!-- Conversations List -->
        <div class="conversations-list">
          <div
            v-for="conversation in filteredConversations"
            :key="conversation.id"
            class="conversation-card"
            :class="{ active: selectedConversation?.id === conversation.id }"
            @click="selectConversation(conversation)"
          >
            <div class="conversation-avatar-wrapper">
              <img :src="conversation.avatar" :alt="conversation.name" class="conversation-avatar" />
              <div v-if="conversation.online" class="online-indicator"></div>
            </div>

            <div class="conversation-details">
              <div class="conversation-top">
                <h4 class="conversation-name">{{ conversation.name }}</h4>
                <span class="conversation-time">{{ conversation.time }}</span>
              </div>
              <p class="conversation-message">{{ conversation.lastMessage }}</p>
            </div>

            <div class="conversation-actions">
              <button v-if="conversation.unread" class="unread-badge">{{ conversation.unread }}</button>
              <button class="icon-btn" @click.stop="togglePin(conversation.id)">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h6a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </aside>

      <!-- Chat Thread (Right Panel) -->
      <div v-if="selectedConversation" class="chat-panel">
        <!-- Chat Header -->
        <div class="chat-header">
          <div class="chat-header-left">
            <img :src="selectedConversation.avatar" :alt="selectedConversation.name" class="chat-avatar" />
            <div class="chat-header-info">
              <h3 class="chat-name">{{ selectedConversation.name }}</h3>
              <p class="chat-status">{{ selectedConversation.online ? 'Online' : 'Offline' }}</p>
            </div>
          </div>

          <div class="chat-header-actions">
            <button class="icon-btn" title="Call">
              <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
              </svg>
            </button>
            <button class="icon-btn" title="Video Call">
              <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
              </svg>
            </button>
            <button class="icon-btn" title="More options">
              <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Messages Area -->
        <div class="messages-area">
          <div v-for="message in selectedConversation.messages" :key="message.id" class="message-group">
            <div class="message-bubble" :class="{ sent: message.sent }">
              <p class="message-text">{{ message.text }}</p>
              <span class="message-time">{{ message.time }}</span>
            </div>
          </div>
        </div>

        <!-- Typing Indicator (if applicable) -->
        <div v-if="isTyping" class="typing-indicator">
          <span></span>
          <span></span>
          <span></span>
        </div>

        <!-- Message Input Area -->
        <div class="message-input-area">
          <button class="input-action-btn" title="Attach file">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
            </svg>
          </button>

          <input
            v-model="messageInput"
            type="text"
            class="message-input"
            placeholder="Type a message..."
            @keydown.enter="sendMessage"
          />

          <button v-if="messageInput.trim()" class="send-btn" @click="sendMessage">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
              <path d="M16.6915026,12.4744748 L3.50612381,13.2599618 C3.19218622,13.2599618 3.03521743,13.4170592 3.03521743,13.5741566 L1.15159189,20.0151496 C0.8376543,20.8006365 0.99,21.89 1.77946707,22.52 C2.41,22.99 3.50612381,23.1 4.13399899,22.8429026 L21.714504,14.0454487 C22.6563168,13.5741566 23.1272231,12.6315722 22.9702544,11.6889879 L4.13399899,1.16346272 C3.34915502,0.9 2.40734225,1.00636533 1.77946707,1.4776575 C0.994623095,2.10604706 0.837654326,3.0486314 1.15159189,3.99021575 L3.03521743,10.4311088 C3.03521743,10.5882061 3.19218622,10.7453035 3.50612381,10.7453035 L16.6915026,11.5307905 C16.6915026,11.5307905 17.1624089,11.5307905 17.1624089,12.0020827 C17.1624089,12.4744748 16.6915026,12.4744748 16.6915026,12.4744748 Z"></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state">
        <div class="empty-icon">
          <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
          </svg>
        </div>
        <h3>No conversation selected</h3>
        <p>Select a conversation to start messaging</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Messages',
  data() {
    return {
      selectedConversation: null,
      messageInput: '',
      isTyping: false,
      activeFilter: 'all',
      searchQuery: '',
      filterTabs: [
        { id: 'all', label: 'All' },
        { id: 'unread', label: 'Unread' },
        { id: 'starred', label: 'Starred' },
      ],
      conversations: [
        {
          id: 1,
          name: 'Sarah Johnson',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Sarah',
          lastMessage: 'That sounds great! Let\'s meet tomorrow at 3 PM.',
          time: '2 min',
          online: true,
          unread: 0,
          messages: [
            { id: 1, text: 'Hi! How are you?', sent: false, time: '10:30 AM' },
            { id: 2, text: 'I\'m doing great, thanks for asking!', sent: true, time: '10:31 AM' },
            { id: 3, text: 'That sounds great! Let\'s meet tomorrow at 3 PM.', sent: false, time: '10:32 AM' },
          ],
        },
        {
          id: 2,
          name: 'Mike Chen',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Mike',
          lastMessage: 'I\'ve sent you the project files.',
          time: '15 min',
          online: true,
          unread: 2,
          messages: [
            { id: 1, text: 'Can you review the files?', sent: false, time: '10:15 AM' },
            { id: 2, text: 'Sure, I\'ll check them out', sent: true, time: '10:16 AM' },
            { id: 3, text: 'I\'ve sent you the project files.', sent: false, time: '10:20 AM' },
          ],
        },
        {
          id: 3,
          name: 'Emma Wilson',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Emma',
          lastMessage: 'Thanks for your help earlier!',
          time: '1 hour',
          online: false,
          unread: 0,
          messages: [
            { id: 1, text: 'Thanks for your help earlier!', sent: false, time: '9:30 AM' },
            { id: 2, text: 'Happy to help anytime!', sent: true, time: '9:31 AM' },
          ],
        },
        {
          id: 4,
          name: 'Design Team',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Team',
          lastMessage: 'New designs are ready for review',
          time: '3 hours',
          online: true,
          unread: 5,
          messages: [
            { id: 1, text: 'New designs are ready for review', sent: false, time: '7:30 AM' },
          ],
        },
      ],
    };
  },
  computed: {
    filteredConversations() {
      let filtered = this.conversations;

      // Filter by search query
      if (this.searchQuery) {
        filtered = filtered.filter(c =>
          c.name.toLowerCase().includes(this.searchQuery.toLowerCase())
        );
      }

      // Filter by tab
      if (this.activeFilter === 'unread') {
        filtered = filtered.filter(c => c.unread > 0);
      }

      return filtered;
    },
  },
  methods: {
    selectConversation(conversation) {
      this.selectedConversation = conversation;
      this.messageInput = '';
    },
    filterConversations(event) {
      this.searchQuery = event.target.value;
    },
    sendMessage() {
      if (!this.messageInput.trim() || !this.selectedConversation) return;

      const newMessage = {
        id: this.selectedConversation.messages.length + 1,
        text: this.messageInput,
        sent: true,
        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
      };

      this.selectedConversation.messages.push(newMessage);
      this.messageInput = '';

      // Simulate typing indicator
      setTimeout(() => {
        this.isTyping = true;
        setTimeout(() => {
          this.isTyping = false;
          const reply = {
            id: this.selectedConversation.messages.length + 1,
            text: 'Thanks for your message!',
            sent: false,
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
          };
          this.selectedConversation.messages.push(reply);
        }, 1500);
      }, 500);
    },
    togglePin(conversationId) {
      console.log('Toggle pin for conversation:', conversationId);
    },
  },
};
</script>

<style scoped>
.messages-page {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.messages-layout {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 0;
  height: 100%;
  background: white;
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}

/* ============================================
   Conversations Panel (Left)
   ============================================ */

.conversations-panel {
  display: flex;
  flex-direction: column;
  border-right: 1px solid var(--muted-gray);
  background: white;
}

.conversations-header {
  padding: var(--spacing-lg);
  border-bottom: 1px solid var(--muted-gray);
}

.search-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background: var(--background-pearl);
  border-radius: var(--radius-md);
  padding: 0 var(--spacing-md);
  margin-bottom: var(--spacing-md);
}

.search-icon {
  color: var(--muted-text);
  flex-shrink: 0;
}

.search-input {
  flex: 1;
  background: transparent;
  border: none;
  padding: var(--spacing-md) var(--spacing-md);
  font-size: 14px;
  color: var(--text-charcoal);
}

.search-input::placeholder {
  color: var(--muted-text);
}

.search-input:focus {
  outline: none;
}

.filter-tabs {
  display: flex;
  gap: var(--spacing-sm);
}

.filter-tab {
  flex: 1;
  background: transparent;
  border: 1px solid var(--muted-gray);
  border-radius: var(--radius-sm);
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 600;
  color: var(--muted-text);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.filter-tab:hover {
  border-color: var(--primary-purple);
  color: var(--primary-purple);
}

.filter-tab.active {
  background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--primary-purple) 100%);
  border-color: transparent;
  color: white;
}

.conversations-list {
  flex: 1;
  overflow-y: auto;
  padding: var(--spacing-sm) 0;
}

.conversation-card {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-md) var(--spacing-lg);
  cursor: pointer;
  transition: all var(--transition-fast);
  border-left: 3px solid transparent;
  position: relative;
}

.conversation-card:hover {
  background-color: var(--background-pearl);
}

.conversation-card.active {
  background: linear-gradient(135deg, rgba(28, 26, 133, 0.05) 0%, rgba(100, 39, 207, 0.05) 100%);
  border-left-color: var(--primary-purple);
}

.conversation-avatar-wrapper {
  position: relative;
  flex-shrink: 0;
}

.conversation-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  object-fit: cover;
}

.online-indicator {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 10px;
  height: 10px;
  background-color: var(--success);
  border: 2px solid white;
  border-radius: 50%;
}

.conversation-details {
  flex: 1;
  min-width: 0;
}

.conversation-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.conversation-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-charcoal);
  margin: 0;
}

.conversation-time {
  font-size: 12px;
  color: var(--muted-text);
}

.conversation-message {
  font-size: 13px;
  color: var(--muted-text);
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.conversation-actions {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  opacity: 0;
  transition: opacity var(--transition-fast);
}

.conversation-card:hover .conversation-actions {
  opacity: 1;
}

.unread-badge {
  background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--primary-purple) 100%);
  color: white;
  font-size: 11px;
  font-weight: 700;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.icon-btn {
  background: transparent;
  border: none;
  color: var(--muted-text);
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: all var(--transition-fast);
}

.icon-btn:hover {
  background-color: var(--background-pearl);
  color: var(--primary-purple);
}

/* ============================================
   Chat Panel (Right)
   ============================================ */

.chat-panel {
  display: flex;
  flex-direction: column;
  background: white;
}

.chat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-lg);
  border-bottom: 1px solid var(--muted-gray);
}

.chat-header-left {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
}

.chat-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.chat-header-info h3 {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 2px 0;
  color: var(--text-charcoal);
}

.chat-status {
  font-size: 12px;
  color: var(--muted-text);
  margin: 0;
}

.chat-header-actions {
  display: flex;
  gap: var(--spacing-md);
}

.messages-area {
  flex: 1;
  overflow-y: auto;
  padding: var(--spacing-xl);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.message-group {
  display: flex;
  justify-content: flex-start;
}

.message-bubble {
  max-width: 60%;
  background: var(--background-pearl);
  border-radius: var(--radius-lg);
  padding: var(--spacing-md) var(--spacing-lg);
  animation: slideIn 0.2s ease-out;
}

.message-bubble.sent {
  justify-self: flex-end;
  background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--primary-purple) 100%);
  color: white;
  margin-left: auto;
}

.message-text {
  font-size: 14px;
  margin: 0 0 4px 0;
  word-wrap: break-word;
}

.message-time {
  font-size: 12px;
  opacity: 0.7;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.typing-indicator {
  display: flex;
  gap: 4px;
  padding: var(--spacing-md) var(--spacing-lg);
}

.typing-indicator span {
  width: 8px;
  height: 8px;
  background-color: var(--muted-text);
  border-radius: 50%;
  animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes typing {
  0%, 60%, 100% {
    opacity: 0.3;
  }
  30% {
    opacity: 1;
  }
}

.message-input-area {
  display: flex;
  align-items: flex-end;
  gap: var(--spacing-md);
  padding: var(--spacing-lg);
  border-top: 1px solid var(--muted-gray);
  background: white;
}

.input-action-btn {
  background: transparent;
  border: none;
  color: var(--primary-indigo);
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  transition: all var(--transition-fast);
  flex-shrink: 0;
}

.input-action-btn:hover {
  background-color: var(--background-pearl);
}

.message-input {
  flex: 1;
  background: var(--background-pearl);
  border: 1px solid var(--muted-gray);
  border-radius: var(--radius-md);
  padding: var(--spacing-md) var(--spacing-lg);
  font-size: 14px;
  color: var(--text-charcoal);
  resize: none;
  max-height: 100px;
}

.message-input:focus {
  outline: none;
  border-color: var(--primary-purple);
}

.send-btn {
  background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--primary-purple) 100%);
  border: none;
  color: white;
  cursor: pointer;
  padding: 8px 12px;
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.send-btn:hover {
  box-shadow: var(--shadow-gradient);
  transform: scale(1.05);
}

/* ============================================
   Empty State
   ============================================ */

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-lg);
  color: var(--muted-text);
}

.empty-icon {
  color: var(--accent-lavender);
  opacity: 0.5;
}

.empty-state h3 {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-charcoal);
  margin: 0;
}

.empty-state p {
  font-size: 14px;
  margin: 0;
}

@media (max-width: 1024px) {
  .messages-layout {
    grid-template-columns: 280px 1fr;
  }

  .message-bubble {
    max-width: 70%;
  }
}

@media (max-width: 768px) {
  .messages-layout {
    grid-template-columns: 1fr;
  }

  .conversations-panel {
    display: none;
  }

  .message-bubble {
    max-width: 85%;
  }
}
</style>
