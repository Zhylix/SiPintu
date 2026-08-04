module.exports = {
  apps: [
    {
      name: 'sipintu-whatsapp-bot',
      script: 'index.js',
      cwd: './',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '500M',
      env: {
        NODE_ENV: 'production',
        PORT: 3000
      }
    }
  ]
};
