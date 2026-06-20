export default {
  root: './',
  server: {
    port: 5173,
    strictPort: true,
    proxy: {
      '/': {
        target: 'http://localhost', // WAMP
        changeOrigin: true,
        secure: false
      }
    }
  }
}
