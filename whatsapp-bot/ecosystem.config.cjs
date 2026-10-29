module.exports = {
  apps: [
    {
      name: 'sipintu-whatsapp-bot',
      script: 'index.js',
      cwd: __dirname,
      instances: 1,
      exec_mode: 'fork',
      autorestart: true,
      watch: false,
      max_memory_restart: '500M',
      env: {
        NODE_ENV: 'production',
        PORT: 3789
      }
    }
  ]
};
