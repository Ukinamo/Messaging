<template>
  <div class="archive-page">
    <!-- Header with Search and Actions -->
    <div class="archive-header">
      <div class="header-title">
        <h2>Archived Conversations</h2>
        <p class="header-subtitle">{{ archivedConversations.length }} archived conversations</p>
      </div>

      <div class="header-actions">
        <div class="search-wrapper">
          <svg class="search-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
          <input
            type="text"
            class="search-input"
            placeholder="Search archived..."
            @input="filterArchive"
          />
        </div>

        <button v-if="selectedItems.length > 0" class="btn-delete" @click="deleteSelected">
          Delete Selected ({{ selectedItems.length }})
        </button>
      </div>
    </div>

    <!-- Archive List -->
    <div v-if="filteredArchive.length > 0" class="archive-list">
      <div v-for="conversation in filteredArchive" :key="conversation.id" class="archive-item">
        <!-- Checkbox -->
        <input
          type="checkbox"
          class="item-checkbox"
          :checked="isSelected(conversation.id)"
          @change="toggleSelect(conversation.id)"
        />

        <!-- Conversation Info -->
        <div class="archive-info">
          <div class="archive-avatar-wrapper">
            <img :src="conversation.avatar" :alt="conversation.name" class="archive-avatar" />
          </div>

          <div class="archive-details">
            <div class="archive-top">
              <h4 class="archive-name">{{ conversation.name }}</h4>
              <span class="archive-date">{{ conversation.archivedDate }}</span>
            </div>
            <p class="archive-preview">{{ conversation.lastMessage }}</p>
            <div class="archive-meta">
              <span class="meta-item">{{ conversation.messageCount }} messages</span>
              <span class="meta-divider">•</span>
              <span class="meta-item">Last message {{ conversation.lastMessageTime }}</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="archive-actions">
          <button class="action-btn unarchive-btn" @click="unarchive(conversation.id)" title="Unarchive">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
            </svg>
            <span>Unarchive</span>
          </button>

          <button class="action-btn delete-btn" @click="deleteConversation(conversation.id)" title="Delete">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            <span>Delete</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-icon">
        <svg width="80" height="80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
        </svg>
      </div>
      <h3>No archived conversations</h3>
      <p>Archived conversations will appear here</p>
      <router-link to="/dashboard/messages" class="btn btn-primary">
        View Messages
      </router-link>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Archive',
  data() {
    return {
      searchQuery: '',
      selectedItems: [],
      archivedConversations: [
        {
          id: 1,
          name: 'Old Project Team',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Project',
          lastMessage: 'Project completed successfully!',
          archivedDate: 'Mar 15, 2024',
          messageCount: 245,
          lastMessageTime: '2 weeks ago',
        },
        {
          id: 2,
          name: 'John Smith',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=John',
          lastMessage: 'Thanks for the update!',
          archivedDate: 'Mar 10, 2024',
          messageCount: 89,
          lastMessageTime: '3 weeks ago',
        },
        {
          id: 3,
          name: 'Marketing Campaign',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Marketing',
          lastMessage: 'Campaign metrics attached',
          archivedDate: 'Feb 28, 2024',
          messageCount: 156,
          lastMessageTime: '1 month ago',
        },
        {
          id: 4,
          name: 'Support Ticket #2024-001',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Support',
          lastMessage: 'Issue resolved',
          archivedDate: 'Feb 15, 2024',
          messageCount: 34,
          lastMessageTime: '1 month ago',
        },
        {
          id: 5,
          name: 'Client Discussion',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Client',
          lastMessage: 'Meeting scheduled for next week',
          archivedDate: 'Jan 30, 2024',
          messageCount: 67,
          lastMessageTime: '2 months ago',
        },
      ],
    };
  },
  computed: {
    filteredArchive() {
      if (!this.searchQuery) {
        return this.archivedConversations;
      }

      return this.archivedConversations.filter(conversation =>
        conversation.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
        conversation.lastMessage.toLowerCase().includes(this.searchQuery.toLowerCase())
      );
    },
  },
  methods: {
    filterArchive(event) {
      this.searchQuery = event.target.value;
    },
    isSelected(id) {
      return this.selectedItems.includes(id);
    },
    toggleSelect(id) {
      const index = this.selectedItems.indexOf(id);
      if (index > -1) {
        this.selectedItems.splice(index, 1);
      } else {
        this.selectedItems.push(id);
      }
    },
    unarchive(id) {
      const index = this.archivedConversations.findIndex(c => c.id === id);
      if (index > -1) {
        this.archivedConversations.splice(index, 1);
        this.$emit('show-toast', {
          type: 'success',
          message: 'Conversation unarchived',
        });
      }
    },
    deleteConversation(id) {
      if (confirm('Are you sure you want to delete this conversation?')) {
        const index = this.archivedConversations.findIndex(c => c.id === id);
        if (index > -1) {
          this.archivedConversations.splice(index, 1);
          this.$emit('show-toast', {
            type: 'success',
            message: 'Conversation deleted',
          });
        }
      }
    },
    deleteSelected() {
      if (confirm(`Delete ${this.selectedItems.length} conversations?`)) {
        this.archivedConversations = this.archivedConversations.filter(
          c => !this.selectedItems.includes(c.id)
        );
        this.selectedItems = [];
        this.$emit('show-toast', {
          type: 'success',
          message: 'Conversations deleted',
        });
      }
    },
  },
};
</script>

<style scoped>
.archive-page {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xl);
}

/* ============================================
   Header
   ============================================ */

.archive-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-xl);
}

.header-title h2 {
  font-size: 28px;
  font-weight: 700;
  margin: 0 0 4px 0;
  color: var(--text-charcoal);
}

.header-subtitle {
  font-size: 14px;
  color: var(--muted-text);
  margin: 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
}

.search-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background: white;
  border: 1px solid var(--muted-gray);
  border-radius: var(--radius-md);
  padding: 0 var(--spacing-md);
  width: 280px;
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

.btn-delete {
  background: var(--error);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.btn-delete:hover {
  opacity: 0.9;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

/* ============================================
   Archive List
   ============================================ */

.archive-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.archive-item {
  background: white;
  border: 1px solid var(--muted-gray);
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  transition: all var(--transition-base);
}

.archive-item:hover {
  box-shadow: var(--shadow-md);
  border-color: var(--accent-lavender);
}

.item-checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: var(--primary-indigo);
  flex-shrink: 0;
}

.archive-info {
  flex: 1;
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  min-width: 0;
}

.archive-avatar-wrapper {
  flex-shrink: 0;
}

.archive-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
}

.archive-details {
  flex: 1;
  min-width: 0;
}

.archive-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.archive-name {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-charcoal);
  margin: 0;
}

.archive-date {
  font-size: 12px;
  color: var(--muted-text);
}

.archive-preview {
  font-size: 14px;
  color: var(--muted-text);
  margin: 0 0 6px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.archive-meta {
  font-size: 12px;
  color: var(--muted-text);
}

.meta-item {
  display: inline;
}

.meta-divider {
  margin: 0 var(--spacing-sm);
}

.archive-actions {
  display: flex;
  gap: var(--spacing-md);
  opacity: 0;
  transition: opacity var(--transition-fast);
}

.archive-item:hover .archive-actions {
  opacity: 1;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  background: transparent;
  border: 1px solid var(--muted-gray);
  border-radius: var(--radius-md);
  padding: 8px 12px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition-fast);
  white-space: nowrap;
}

.unarchive-btn {
  color: var(--primary-indigo);
  border-color: var(--accent-lavender);
}

.unarchive-btn:hover {
  background-color: rgba(28, 26, 133, 0.05);
  border-color: var(--primary-indigo);
}

.delete-btn {
  color: var(--error);
  border-color: rgba(239, 68, 68, 0.3);
}

.delete-btn:hover {
  background-color: rgba(239, 68, 68, 0.05);
  border-color: var(--error);
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
  padding: var(--spacing-4xl) var(--spacing-xl);
  background: white;
  border: 1px solid var(--muted-gray);
  border-radius: var(--radius-lg);
  text-align: center;
}

.empty-icon {
  color: var(--accent-lavender);
  opacity: 0.5;
}

.empty-state h3 {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-charcoal);
  margin: 0;
}

.empty-state p {
  font-size: 14px;
  color: var(--muted-text);
  margin: 0;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 24px;
  border: none;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition-fast);
  text-decoration: none;
}

.btn-primary {
  background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--primary-purple) 100%);
  color: white;
  box-shadow: var(--shadow-gradient);
}

.btn-primary:hover {
  box-shadow: 0 8px 25px rgba(100, 39, 207, 0.3);
  transform: translateY(-2px);
}

@media (max-width: 768px) {
  .archive-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .header-actions {
    flex-direction: column;
    width: 100%;
  }

  .search-wrapper {
    width: 100%;
  }

  .btn-delete {
    width: 100%;
  }

  .archive-item {
    flex-direction: column;
    align-items: flex-start;
  }

  .archive-info {
    width: 100%;
  }

  .archive-actions {
    width: 100%;
    opacity: 1;
  }

  .action-btn {
    flex: 1;
    justify-content: center;
  }
}
</style>
