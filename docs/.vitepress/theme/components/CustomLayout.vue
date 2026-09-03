<script setup lang="ts">
import DefaultTheme from 'vitepress/theme';
import { useData } from 'vitepress';

const { Layout } = DefaultTheme;
const { frontmatter } = useData();
</script>

<template>
  <Layout>
    <template #layout-top>
      <div v-if="frontmatter.sponsor" class="sponsor-notice">
        <div class="sponsor-notice-content">
          <span class="sponsor-badge">Sponsor Content</span>
          <p>This content requires sponsorship to access.</p>
          <a href="https://github.com/sponsors/dipaksarkar" target="_blank" class="sponsor-link">
            Become a Sponsor →
          </a>
        </div>
      </div>
    </template>
    <template #layout-bottom>
      <footer class="custom-footer">
        <div class="footer-content">
          <p>© 2024-2026 Dipak Sarkar. All rights reserved.</p>
          <p>
            Built with ❤️ for Laravel developers
          </p>
        </div>
      </footer>
    </template>
  </Layout>
</template>

<style scoped>
.sponsor-notice {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  border-bottom: 1px solid #f59e0b;
  padding: 1rem;
  text-align: center;
}

.dark .sponsor-notice {
  background: linear-gradient(135deg, #451a03, #78350f);
}

.sponsor-notice-content {
  max-width: 1200px;
  margin: 0 auto;
}

.sponsor-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #f59e0b;
  color: white;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.sponsor-notice p {
  margin: 0.5rem 0;
  font-size: 0.875rem;
}

.sponsor-link {
  color: #d97706;
  font-weight: 600;
  text-decoration: none;
}

.sponsor-link:hover {
  text-decoration: underline;
}

.custom-footer {
  border-top: 1px solid var(--vp-c-divider);
  padding: 2rem;
  text-align: center;
  background: var(--vp-c-bg-soft);
}

.footer-content p {
  margin: 0.25rem 0;
  font-size: 0.875rem;
  color: var(--vp-c-text-2);
}
</style>
