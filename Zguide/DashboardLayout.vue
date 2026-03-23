<template>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar" :class="{ 'sidebar-open': sidebarOpen }">
      <!-- User Profile Section -->
      <div class="sidebar-profile">
        <div class="profile-avatar">
          <img :src="userAvatar" :alt="userName" />
          <div class="status-indicator online"></div>
        </div>
        <div class="profile-info">
          <h3>{{ userName }}</h3>
          <p class="status-text">Online</p>
        </div>
      </div>

      <!-- Navigation Menu -->
      <nav class="sidebar-nav">
        <router-link
          v-for="item in navItems"
          :key="item.id"
          :to="item.path"
          class="nav-item"
          :class="{ active: isActive(item.path) }"
        >
          <component :is="item.icon" class="nav-icon" />
          <span class="nav-label">{{ item.label }}</span>
          <span v-if="item.badge" class="nav-badge">{{ item.badge }}</span>
        </router-link>
      </nav>

      <!-- Sidebar Footer -->
      <div class="sidebar-footer">
        <button class="sidebar-btn" @click="openSettings" title="Settings">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
        </button>
        <button class="sidebar-btn" @click="logout" title="Logout">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
          </svg>
        </button>
      </div>
    </aside>

    <!-- Mobile Sidebar Toggle -->
    <button v-if="isMobile" class="sidebar-toggle" @click="toggleSidebar">
      <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
      </svg>
    </button>

    <!-- Main Content -->
    <main class="main-content">
      <!-- Header -->
      <header class="dashboard-header">
        <div class="header-left">
          <h1 class="page-title">{{ pageTitle }}</h1>
        </div>

        <div class="header-right">
          <!-- Search Bar -->
          <div class="search-wrapper">
            <svg class="search-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input
              type="text"
              class="search-input"
              placeholder="Search conversations..."
              @input="handleSearch"
            />
          </div>

          <!-- Notifications -->
          <button class="header-btn notification-btn" @click="openNotifications">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <span v-if="notificationCount > 0" class="notification-badge">{{ notificationCount }}</span>
          </button>

          <!-- User Menu -->
          <div class="user-menu">
            <button class="user-menu-btn" @click="toggleUserMenu">
              <img :src="userAvatar" :alt="userName" class="user-avatar-small" />
              <span class="user-name-small">{{ userName }}</span>
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
              </svg>
            </button>

            <!-- User Dropdown Menu -->
            <div v-if="userMenuOpen" class="user-dropdown">
              <a href="#" class="dropdown-item">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span>Profile</span>
              </a>
              <a href="#" class="dropdown-item">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                </svg>
                <span>Settings</span>
              </a>
              <div class="dropdown-divider"></div>
              <button class="dropdown-item logout-btn" @click="logout">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span>Logout</span>
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <div class="content-area">
        <router-view />
      </div>
    </main>

    <!-- Sidebar Overlay (Mobile) -->
    <div v-if="isMobile && sidebarOpen" class="sidebar-overlay" @click="toggleSidebar"></div>
  </div>
</template>

<script>
export default {
  name: 'DashboardLayout',
  data() {
    return {
      sidebarOpen: false,
      userMenuOpen: false,
      isMobile: false,
      userName: 'John Doe',
      userAvatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=John',
      notificationCount: 3,
      pageTitle: 'Dashboard',
      navItems: [
        {
          id: 1,
          label: 'Dashboard',
          path: '/dashboard',
          icon: 'DashboardIcon',
          badge: null,
        },
        {
          id: 2,
          label: 'Messages',
          path: '/dashboard/messages',
          icon: 'MessagesIcon',
          badge: 5,
        },
        {
          id: 3,
          label: 'Archive',
          path: '/dashboard/archive',
          icon: 'ArchiveIcon',
          badge: null,
        },
        {
          id: 4,
          label: 'Blocked',
          path: '/dashboard/blocked',
          icon: 'BlockedIcon',
          badge: null,
        },
      ],
    };
  },
  computed: {
    pageTitle() {
      const route = this.$route.path;
      if (route.includes('messages')) return 'Messages';
      if (route.includes('archive')) return 'Archive';
      if (route.includes('blocked')) return 'Blocked Users';
      return 'Dashboard';
    },
  },
  methods: {
    toggleSidebar() {
      this.sidebarOpen = !this.sidebarOpen;
    },
    toggleUserMenu() {
      this.userMenuOpen = !this.userMenuOpen;
    },
    isActive(path) {
      return this.$route.path === path;
    },
    handleSearch(event) {
      this.$emit('search', event.target.value);
    },
    openNotifications() {
      console.log('Open notifications');
    },
    openSettings() {
      this.$router.push('/dashboard/settings');
    },
    logout() {
      localStorage.removeItem('authToken');
      this.$router.push('/login');
    },
    handleResize() {
      this.isMobile = window.innerWidth < 768;
      if (!this.isMobile) {
        this.sidebarOpen = false;
      }
    },
  },
  mounted() {
    this.handleResize();
    window.addEventListener('resize', this.handleResize);
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.handleResize);
  },
};

// Icon components
const DashboardIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 11l4-4"></path></svg>`,
};

const MessagesIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>`,
};

const ArchiveIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>`,
};

const BlockedIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0-2C6.48 6 2 9.58 2 14s4.48 8 10 8 10-3.58 10-8-4.48-8-10-8zm3.5 9H8.5V13h7v-2z"></path></svg>`,
};

export default {
  components: {
    DashboardIcon,
    MessagesIcon,
    ArchiveIcon,
    BlockedIcon,
  },
};
</script>

<style scoped>
.dashboard-container {
  display: flex;
  min-height: 100vh;
  background-color: var(--background-pearl);
}

/* ============================================
   Sidebar
   ============================================ */

.sidebar {
  width: 280px;
  background: white;
  border-right: 1px solid var(--muted-gray);
  display: flex;
  flex-direction: column;
  box-shadow: var(--shadow-sm);
  transition: all var(--transition-base);
}

.sidebar-profile {
  padding: var(--spacing-lg);
  border-bottom: 1px solid var(--muted-gray);
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
}

.profile-avatar {
  position: relative;
  width: 48px;
  height: 48px;
  flex-shrink: 0;
}

.profile-avatar img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.status-indicator {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid white;
}

.status-indicator.online {
  background-color: var(--success);
}

.status-indicator.offline {
  background-color: #9CA3AF;
}

.profile-info h3 {
  font-size: 16px;
  font-weight: 600;
  margin: 0;
  color: var(--text-charcoal);
}

.profile-info .status-text {
  font-size: 12px;
  color: var(--muted-text);
  margin: 0;
}

.sidebar-nav {
  flex: 1;
  padding: var(--spacing-md) 0;
  overflow-y: auto;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-md) var(--spacing-lg);
  color: var(--text-charcoal);
  text-decoration: none;
  transition: all var(--transition-fast);
  position: relative;
}

.nav-item:hover {
  background-color: rgba(165, 139, 215, 0.05);
}

.nav-item.active {
  background: linear-gradient(135deg, rgba(28, 26, 133, 0.1) 0%, rgba(100, 39, 207, 0.1) 100%);
  color: var(--primary-indigo);
  font-weight: 600;
}

.nav-item.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--primary-purple) 100%);
}

.nav-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  color: currentColor;
}

.nav-label {
  flex: 1;
  font-size: 16px;
  font-weight: 500;
}

.nav-badge {
  background: linear-gradient(135deg, var(--primary-indigo) 0%, var(--primary-purple) 100%);
  color: white;
  font-size: 12px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: var(--radius-full);
  min-width: 24px;
  text-align: center;
}

.sidebar-footer {
  padding: var(--spacing-lg);
  border-top: 1px solid var(--muted-gray);
  display: flex;
  gap: var(--spacing-md);
}

.sidebar-btn {
  flex: 1;
  background: transparent;
  border: 1px solid var(--muted-gray);
  border-radius: var(--radius-md);
  padding: var(--spacing-md);
  color: var(--text-charcoal);
  cursor: pointer;
  transition: all var(--transition-fast);
  display: flex;
  align-items: center;
  justify-content: center;
}

.sidebar-btn:hover {
  background-color: rgba(165, 139, 215, 0.05);
  border-color: var(--primary-purple);
  color: var(--primary-purple);
}

/* ============================================
   Mobile Sidebar
   ============================================ */

.sidebar-toggle {
  display: none;
  position: fixed;
  top: 16px;
  left: 16px;
  z-index: 40;
  background: white;
  border: 1px solid var(--muted-gray);
  border-radius: var(--radius-md);
  padding: 8px;
  cursor: pointer;
  color: var(--text-charcoal);
}

.sidebar-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 30;
}

@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    z-index: 35;
    transform: translateX(-100%);
  }

  .sidebar.sidebar-open {
    transform: translateX(0);
  }

  .sidebar-toggle {
    display: flex;
  }

  .sidebar-overlay.show {
    display: block;
  }
}

/* ============================================
   Main Content
   ============================================ */

.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.dashboard-header {
  background: white;
  border-bottom: 1px solid var(--muted-gray);
  padding: var(--spacing-lg) var(--spacing-xl);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-xl);
  box-shadow: var(--shadow-sm);
  height: 64px;
  flex-shrink: 0;
}

.header-left {
  flex: 1;
}

.page-title {
  font-size: 24px;
  font-weight: 700;
  margin: 0;
  color: var(--text-charcoal);
}

.header-right {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
}

.search-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background: var(--background-pearl);
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

.header-btn {
  background: transparent;
  border: none;
  color: var(--text-charcoal);
  cursor: pointer;
  padding: 8px;
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.header-btn:hover {
  background-color: rgba(165, 139, 215, 0.05);
  color: var(--primary-purple);
}

.notification-btn {
  position: relative;
}

.notification-badge {
  position: absolute;
  top: 0;
  right: 0;
  background: var(--error);
  color: white;
  font-size: 10px;
  font-weight: 700;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-menu {
  position: relative;
}

.user-menu-btn {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  background: transparent;
  border: 1px solid var(--muted-gray);
  border-radius: var(--radius-md);
  padding: 6px 12px;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.user-menu-btn:hover {
  background-color: rgba(165, 139, 215, 0.05);
  border-color: var(--primary-purple);
}

.user-avatar-small {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  object-fit: cover;
}

.user-name-small {
  font-size: 14px;
  font-weight: 500;
  color: var(--text-charcoal);
}

.user-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid var(--muted-gray);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  min-width: 200px;
  margin-top: 8px;
  z-index: 50;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-md) var(--spacing-lg);
  color: var(--text-charcoal);
  text-decoration: none;
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 14px;
  width: 100%;
  text-align: left;
  transition: all var(--transition-fast);
}

.dropdown-item:hover {
  background-color: var(--background-pearl);
}

.dropdown-divider {
  height: 1px;
  background-color: var(--muted-gray);
  margin: var(--spacing-sm) 0;
}

.logout-btn {
  color: var(--error);
}

.content-area {
  flex: 1;
  overflow-y: auto;
  padding: var(--spacing-xl);
}

@media (max-width: 768px) {
  .dashboard-header {
    padding: var(--spacing-md) var(--spacing-lg);
  }

  .page-title {
    font-size: 20px;
  }

  .search-wrapper {
    display: none;
  }

  .header-right {
    gap: var(--spacing-md);
  }

  .user-name-small {
    display: none;
  }

  .content-area {
    padding: var(--spacing-lg);
  }
}
</style>
