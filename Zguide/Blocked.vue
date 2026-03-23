<template>
  <div class="blocked-page">
    <!-- Header with Search and Actions -->
    <div class="blocked-header">
      <div class="header-title">
        <h2>Blocked Users</h2>
        <p class="header-subtitle">{{ blockedUsers.length }} blocked users</p>
      </div>

      <div class="header-actions">
        <div class="search-wrapper">
          <svg class="search-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
          <input
            type="text"
            class="search-input"
            placeholder="Search blocked users..."
            @input="filterBlocked"
          />
        </div>

        <button v-if="selectedItems.length > 0" class="btn-unblock-all" @click="unblockSelected">
          Unblock Selected ({{ selectedItems.length }})
        </button>
      </div>
    </div>

    <!-- Blocked Users List -->
    <div v-if="filteredBlocked.length > 0" class="blocked-list">
      <div v-for="user in filteredBlocked" :key="user.id" class="blocked-card">
        <!-- Checkbox -->
        <input
          type="checkbox"
          class="card-checkbox"
          :checked="isSelected(user.id)"
          @change="toggleSelect(user.id)"
        />

        <!-- User Info -->
        <div class="user-info">
          <img :src="user.avatar" :alt="user.name" class="user-avatar" />

          <div class="user-details">
            <h4 class="user-name">{{ user.name }}</h4>
            <p class="user-email">{{ user.email }}</p>
            <p class="block-date">Blocked {{ user.blockedDate }}</p>
          </div>
        </div>

        <!-- Actions -->
        <div class="card-actions">
          <button class="action-btn view-btn" @click="viewProfile(user.id)" title="View Profile">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Profile</span>
          </button>

          <button class="action-btn unblock-btn" @click="unblockUser(user.id)" title="Unblock">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            <span>Unblock</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-icon">
        <svg width="80" height="80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
        </svg>
      </div>
      <h3>No blocked users</h3>
      <p>Users you block will appear here</p>
      <router-link to="/dashboard/messages" class="btn btn-primary">
        Back to Messages
      </router-link>
    </div>

    <!-- Bulk Actions Info -->
    <div v-if="selectedItems.length > 0" class="bulk-actions-info">
      <p>{{ selectedItems.length }} user{{ selectedItems.length !== 1 ? 's' : '' }} selected</p>
      <button class="btn-clear-selection" @click="clearSelection">Clear</button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Blocked',
  data() {
    return {
      searchQuery: '',
      selectedItems: [],
      blockedUsers: [
        {
          id: 1,
          name: 'Alex Thompson',
          email: 'alex.thompson@example.com',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Alex',
          blockedDate: '2 weeks ago',
        },
        {
          id: 2,
          name: 'Jordan Martinez',
          email: 'jordan.m@example.com',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Jordan',
          blockedDate: '1 month ago',
        },
        {
          id: 3,
          name: 'Casey Lee',
          email: 'casey.lee@example.com',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Casey',
          blockedDate: '2 months ago',
        },
        {
          id: 4,
          name: 'Morgan Davis',
          email: 'morgan.davis@example.com',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Morgan',
          blockedDate: '3 months ago',
        },
        {
          id: 5,
          name: 'Taylor Wilson',
          email: 'taylor.w@example.com',
          avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Taylor',
          blockedDate: '4 months ago',
        },
      ],
    };
  },
  computed: {
    filteredBlocked() {
      if (!this.searchQuery) {
        return this.blockedUsers;
      }

      return this.blockedUsers.filter(user =>
        user.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
        user.email.toLowerCase().includes(this.searchQuery.toLowerCase())
      );
    },
  },
  methods: {
    filterBlocked(event) {
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
    clearSelection() {
      this.selectedItems = [];
    },
    unblockUser(id) {
      const index = this.blockedUsers.findIndex(u => u.id === id);
      if (index > -1) {
        const userName = this.blockedUsers[index].name;
        this.blockedUsers.splice(index, 1);
        this.$emit('show-toast', {
          type: 'success',
          message: `${userName} has been unblocked`,
        });
      }
    },
    unblockSelected() {
      if (confirm(`Unblock ${this.selectedItems.length} user${this.selectedItems.length !== 1 ? 's' : ''}?`)) {
        this.blockedUsers = this.blockedUsers.filter(
          u => !this.selectedItems.includes(u.id)
        );
        this.selectedItems = [];
        this.$emit('show-toast', {
          type: 'success',
          message: 'Users unblocked',
        });
      }
    },
    viewProfile(userId) {
      const user = this.blockedUsers.find(u => u.id === userId);
      if (user) {
        console.log('View profile for:', user.name);
        // Navigate to profile page or open modal
      }
    },
  },
};
</script>

<style scoped>
.blocked-page {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xl);
}

/* ============================================
   Header
   ============================================ */

.blocked-header {
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

.btn-unblock-all {
  background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--primary-purple) 100%);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition-fast);
  box-shadow: var(--shadow-gradient);
}

.btn-unblock-all:hover {
  box-shadow: 0 8px 25px rgba(100, 39, 207, 0.3);
  transform: translateY(-2px);
}

/* ============================================
   Blocked Users List
   ============================================ */

.blocked-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: var(--spacing-lg);
}

.blocked-card {
  background: white;
  border: 1px solid var(--muted-gray);
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-lg);
  transition: all var(--transition-base);
}

.blocked-card:hover {
  box-shadow: var(--shadow-md);
  border-color: var(--accent-lavender);
}

.card-checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: var(--primary-indigo);
  flex-shrink: 0;
  margin-top: 4px;
}

.user-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.user-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  object-fit: cover;
  align-self: center;
}

.user-details {
  text-align: center;
}

.user-name {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-charcoal);
  margin: 0;
}

.user-email {
  font-size: 14px;
  color: var(--muted-text);
  margin: 4px 0;
  word-break: break-all;
}

.block-date {
  font-size: 12px;
  color: var(--muted-text);
  margin: 0;
}

.card-actions {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  width: 100%;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
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

.view-btn {
  color: var(--primary-indigo);
  border-color: var(--accent-lavender);
}

.view-btn:hover {
  background-color: rgba(28, 26, 133, 0.05);
  border-color: var(--primary-indigo);
}

.unblock-btn {
  color: var(--success);
  border-color: rgba(16, 185, 129, 0.3);
}

.unblock-btn:hover {
  background-color: rgba(16, 185, 129, 0.05);
  border-color: var(--success);
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

/* ============================================
   Bulk Actions Info
   ============================================ */

.bulk-actions-info {
  position: fixed;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  background: white;
  border: 1px solid var(--muted-gray);
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg) var(--spacing-xl);
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  box-shadow: var(--shadow-lg);
  z-index: 40;
}

.bulk-actions-info p {
  margin: 0;
  font-weight: 600;
  color: var(--text-charcoal);
}

.btn-clear-selection {
  background: transparent;
  border: 1px solid var(--muted-gray);
  border-radius: var(--radius-md);
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-charcoal);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.btn-clear-selection:hover {
  background-color: var(--background-pearl);
  border-color: var(--primary-purple);
}

@media (max-width: 768px) {
  .blocked-header {
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

  .btn-unblock-all {
    width: 100%;
  }

  .blocked-list {
    grid-template-columns: 1fr;
  }

  .blocked-card {
    flex-direction: row;
  }

  .user-info {
    flex-direction: row;
    gap: var(--spacing-md);
  }

  .user-details {
    text-align: left;
    flex: 1;
  }

  .user-avatar {
    width: 48px;
    height: 48px;
    align-self: flex-start;
  }

  .card-actions {
    flex-direction: row;
    width: auto;
  }

  .action-btn {
    flex: 1;
  }
}
</style>
