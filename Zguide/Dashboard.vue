<template>
  <div class="dashboard-page">
    <!-- Overview Cards -->
    <div class="overview-grid">
      <div v-for="card in overviewCards" :key="card.id" class="overview-card">
        <div class="card-icon" :style="{ background: card.gradient }">
          <component :is="card.icon" />
        </div>
        <div class="card-content">
          <p class="card-label">{{ card.label }}</p>
          <h3 class="card-value">{{ card.value }}</h3>
        </div>
      </div>
    </div>

    <!-- Recent Conversations Section -->
    <section class="recent-section">
      <div class="section-header">
        <h2>Recent Conversations</h2>
        <router-link to="/dashboard/messages" class="view-all-link">View All</router-link>
      </div>

      <div class="conversations-list">
        <div v-for="conversation in recentConversations" :key="conversation.id" class="conversation-item">
          <div class="conversation-avatar-wrapper">
            <img :src="conversation.avatar" :alt="conversation.name" class="conversation-avatar" />
            <div v-if="conversation.online" class="online-badge"></div>
          </div>

          <div class="conversation-info">
            <div class="conversation-header">
              <h4 class="conversation-name">{{ conversation.name }}</h4>
              <span class="conversation-time">{{ conversation.time }}</span>
            </div>
            <p class="conversation-preview">{{ conversation.lastMessage }}</p>
          </div>

          <div v-if="conversation.unread" class="unread-badge">{{ conversation.unread }}</div>
        </div>
      </div>
    </section>

    <!-- Quick Actions Section -->
    <section class="quick-actions-section">
      <h2>Quick Actions</h2>
      <div class="actions-grid">
        <button class="action-card" @click="startNewConversation">
          <div class="action-icon">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
          </div>
          <span>New Conversation</span>
        </button>

        <button class="action-card" @click="searchMessages">
          <div class="action-icon">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
          </div>
          <span>Search Messages</span>
        </button>

        <button class="action-card" @click="viewArchive">
          <div class="action-icon">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
            </svg>
          </div>
          <span>View Archive</span>
        </button>

        <button class="action-card" @click="manageBlocked">
          <div class="action-icon">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
          </div>
          <span>Manage Blocked</span>
        </button>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  name: 'Dashboard',
  data() {
    return {
      overviewCards: [
        {
          id: 1,
          label: 'Total Conversations',
          value: '24',
          icon: 'ConversationsIcon',
          gradient: 'linear-gradient(135deg, rgba(28, 26, 133, 0.1) 0%, rgba(100, 39, 207, 0.1) 100%)',
        },
        {
          id: 2,
          label: 'Unread Messages',
          value: '8',
          icon: 'UnreadIcon',
          gradient: 'linear-gradient(135deg, rgba(100, 39, 207, 0.1) 0%, rgba(165, 139, 215, 0.1) 100%)',
        },
        {
          id: 3,
          label: 'Online Contacts',
          value: '12',
          icon: 'OnlineIcon',
          gradient: 'linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(52, 211, 153, 0.1) 100%)',
        },
        {
          id: 4,
          label: 'Archived',
          value: '5',
          icon: 'ArchiveIcon',
          gradient: 'linear-gradient(135deg, rgba(107, 107, 127, 0.1) 0%, rgba(156, 163, 175, 0.1) 100%)',
        },
      ],
      recentConversations: [
        {
          id: 1,
          name: 'Sarah Johnson',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Sarah',
          lastMessage: 'That sounds great! Let\'s meet tomorrow at 3 PM.',
          time: '2 min',
          online: true,
          unread: 0,
        },
        {
          id: 2,
          name: 'Mike Chen',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Mike',
          lastMessage: 'I\'ve sent you the project files.',
          time: '15 min',
          online: true,
          unread: 2,
        },
        {
          id: 3,
          name: 'Emma Wilson',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Emma',
          lastMessage: 'Thanks for your help earlier!',
          time: '1 hour',
          online: false,
          unread: 0,
        },
        {
          id: 4,
          name: 'Design Team',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Team',
          lastMessage: 'New designs are ready for review',
          time: '3 hours',
          online: true,
          unread: 5,
        },
        {
          id: 5,
          name: 'Alex Kumar',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Alex',
          lastMessage: 'See you at the meeting!',
          time: '5 hours',
          online: false,
          unread: 0,
        },
      ],
    };
  },
  methods: {
    startNewConversation() {
      this.$router.push('/dashboard/messages');
    },
    searchMessages() {
      console.log('Search messages');
    },
    viewArchive() {
      this.$router.push('/dashboard/archive');
    },
    manageBlocked() {
      this.$router.push('/dashboard/blocked');
    },
  },
};

// Icon components
const ConversationsIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>`,
};

const UnreadIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>`,
};

const OnlineIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0-2C6.48 6 2 9.58 2 14s4.48 8 10 8 10-3.58 10-8-4.48-8-10-8z"></path></svg>`,
};

const ArchiveIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>`,
};

export default {
  components: {
    ConversationsIcon,
    UnreadIcon,
    OnlineIcon,
    ArchiveIcon,
  },
};
</script>

<style scoped>
.dashboard-page {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2xl);
}

/* ============================================
   Overview Cards
   ============================================ */

.overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: var(--spacing-lg);
}

.overview-card {
  background: white;
  border: 1px solid var(--muted-gray);
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  transition: all var(--transition-base);
  box-shadow: var(--shadow-sm);
}

.overview-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.card-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary-indigo);
  flex-shrink: 0;
}

.card-icon svg {
  width: 28px;
  height: 28px;
}

.card-content {
  flex: 1;
}

.card-label {
  font-size: 12px;
  color: var(--muted-text);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 0 4px 0;
  font-weight: 600;
}

.card-value {
  font-size: 32px;
  font-weight: 700;
  color: var(--text-charcoal);
  margin: 0;
}

/* ============================================
   Recent Conversations
   ============================================ */

.recent-section {
  background: white;
  border: 1px solid var(--muted-gray);
  border-radius: var(--radius-lg);
  padding: var(--spacing-xl);
  box-shadow: var(--shadow-sm);
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-lg);
}

.section-header h2 {
  font-size: 20px;
  font-weight: 700;
  margin: 0;
  color: var(--text-charcoal);
}

.view-all-link {
  color: var(--primary-purple);
  font-weight: 600;
  text-decoration: none;
  transition: color var(--transition-fast);
}

.view-all-link:hover {
  color: var(--primary-indigo);
}

.conversations-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.conversation-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  padding: var(--spacing-md);
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
  cursor: pointer;
}

.conversation-item:hover {
  background-color: var(--background-pearl);
}

.conversation-avatar-wrapper {
  position: relative;
  flex-shrink: 0;
}

.conversation-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
}

.online-badge {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 12px;
  height: 12px;
  background-color: var(--success);
  border: 2px solid white;
  border-radius: 50%;
}

.conversation-info {
  flex: 1;
  min-width: 0;
}

.conversation-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.conversation-name {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-charcoal);
  margin: 0;
}

.conversation-time {
  font-size: 12px;
  color: var(--muted-text);
}

.conversation-preview {
  font-size: 14px;
  color: var(--muted-text);
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.unread-badge {
  background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--primary-purple) 100%);
  color: white;
  font-size: 12px;
  font-weight: 700;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* ============================================
   Quick Actions
   ============================================ */

.quick-actions-section h2 {
  font-size: 20px;
  font-weight: 700;
  margin: 0 0 var(--spacing-lg) 0;
  color: var(--text-charcoal);
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--spacing-lg);
}

.action-card {
  background: white;
  border: 1px solid var(--muted-gray);
  border-radius: var(--radius-lg);
  padding: var(--spacing-xl);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-md);
  cursor: pointer;
  transition: all var(--transition-base);
  font-size: 16px;
  font-weight: 600;
  color: var(--text-charcoal);
  box-shadow: var(--shadow-sm);
}

.action-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-4px);
  border-color: var(--primary-purple);
}

.action-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, rgba(28, 26, 133, 0.1) 0%, rgba(100, 39, 207, 0.1) 100%);
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary-indigo);
}

.action-icon svg {
  width: 24px;
  height: 24px;
}

@media (max-width: 768px) {
  .overview-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .card-value {
    font-size: 24px;
  }

  .actions-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .action-card {
    padding: var(--spacing-lg);
  }
}

@media (max-width: 480px) {
  .overview-grid {
    grid-template-columns: 1fr;
  }

  .actions-grid {
    grid-template-columns: 1fr;
  }
}
</style>
