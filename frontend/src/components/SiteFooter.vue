<script setup lang="ts">
defineProps<{
  context?: string
}>()
</script>

<template>
  <footer class="site-footer">
    <div class="container footer-main">
      <div class="footer-brand">
        <a href="/" class="footer-logo">Jejak<span>Barang</span></a>
        <p class="footer-description">
          Platform informasi barang hilang dan ditemukan untuk lingkungan kampus yang lebih tertib dan saling membantu.
        </p>
        <span class="footer-status">
          <span class="status-dot" aria-hidden="true"></span>
          Sistem berjalan normal
        </span>
      </div>

      <div class="footer-column">
        <h2>Jelajahi</h2>
        <a href="/">Beranda</a>
        <a href="/report">Laporkan Barang</a>
        <a href="/login">Masuk</a>
        <a href="/register">Daftar Akun</a>
      </div>

      <div class="footer-column">
        <h2>Panduan</h2>
        <span>Cari barang di papan publik</span>
        <span>Laporkan barang dengan detail</span>
        <span>Tunggu verifikasi admin</span>
        <span>Gunakan kontak secara bijak</span>
      </div>

      <div class="footer-column footer-context">
        <h2>Tentang aplikasi</h2>
        <span>{{ context || 'Sistem Informasi Lost &amp; Found Kampus' }}</span>
        <span>Konstruksi dan Evolusi Perangkat Lunak</span>
        <span>Vue.js · Laravel · Sanctum</span>
      </div>
    </div>

    <div class="container footer-bottom">
      <span>&copy; 2026 JejakBarang. Dibuat untuk komunitas kampus.</span>
      <span>Privasi kontak dijaga sesuai status akses.</span>
    </div>
  </footer>
</template>

<style scoped>
.site-footer {
  margin-top: auto;
  background: var(--color-card);
  border-top: 1px solid var(--color-border);
}

.footer-main {
  display: grid;
  grid-template-columns: minmax(240px, 1.8fr) repeat(3, minmax(140px, 1fr));
  gap: var(--space-8);
  padding-top: 42px;
  padding-bottom: 34px;
}

.footer-brand {
  max-width: 310px;
}

.footer-logo {
  color: var(--color-primary);
  font-size: 20px;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.footer-logo span {
  color: var(--color-text);
}

.footer-description {
  margin-top: var(--space-3);
  color: var(--color-muted);
  font-size: 13px;
  line-height: 1.65;
}

.footer-status {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  margin-top: var(--space-4);
  color: var(--color-success);
  font-size: 12px;
  font-weight: 600;
}

.status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--color-success);
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-success) 15%, transparent);
}

.footer-column {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 10px;
}

.footer-column h2 {
  margin-bottom: 5px;
  color: var(--color-text);
  font-size: 13px;
  font-weight: 700;
}

.footer-column a,
.footer-column span {
  color: var(--color-muted);
  font-size: 12px;
  line-height: 1.5;
}

.footer-column a:hover {
  color: var(--color-primary);
}

.footer-bottom {
  display: flex;
  justify-content: space-between;
  gap: var(--space-4);
  padding-top: 16px;
  padding-bottom: 18px;
  border-top: 1px solid var(--color-border);
  color: var(--color-muted);
  font-size: 11px;
}

@media (max-width: 800px) {
  .footer-main {
    grid-template-columns: repeat(2, 1fr);
  }

  .footer-brand {
    grid-column: 1 / -1;
    max-width: 520px;
  }
}

@media (max-width: 520px) {
  .footer-main {
    grid-template-columns: 1fr 1fr;
    gap: var(--space-6) var(--space-4);
    padding-top: 32px;
  }

  .footer-bottom {
    flex-direction: column;
    gap: 6px;
  }
}
</style>
