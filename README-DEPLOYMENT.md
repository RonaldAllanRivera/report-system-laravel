# Deployment Guide: Laravel Application on Render.com

This guide provides step-by-step instructions for deploying the Laravel application to Render.com.

## Prerequisites

- A GitHub/GitLab/Bitbucket account with access to your repository
- A Render.com account (sign up at [https://render.com](https://render.com) if needed)
- Your database credentials (if using an external database)
- Google OAuth credentials (if using Google authentication)

## Deployment Steps

### 1. Prepare Your Repository

Ensure your repository includes the following files:
- `render.yaml` - Render configuration
- `deploy.sh` - Deployment script
- `.env.example` - Example environment variables
- `.renderignore` - Files to exclude from deployment
- `Dockerfile` (optional, for custom container deployment)

### 2. Set Up Environment Variables

Before deploying, you'll need to set up the following required environment variables in the Render dashboard:

#### Required Environment Variables

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-generated-key-here
APP_URL=https://your-render-app.onrender.com

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=your-db-name
DB_USERNAME=your-db-username
DB_PASSWORD=your-db-password

# Google OAuth (if applicable)
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=https://your-render-app.onrender.com/google/callback
```

#### Optional Environment Variables

```
# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@example.com
MAIL_FROM_NAME="${APP_NAME}"

# Cache and Session
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Google Drive (if applicable)
GOOGLE_DRIVE_DAILY_PARENT_ID=your-folder-id
GOOGLE_DRIVE_WEEKLY_PARENT_ID=your-folder-id
GOOGLE_DRIVE_MONTHLY_PARENT_ID=your-folder-id
GOOGLE_DRIVE_DEFAULT_PARENT_ID=your-folder-id
```

### 3. Deploy to Render

#### Option 1: Using Render Dashboard (Recommended)

1. Log in to your [Render Dashboard](https://dashboard.render.com/)
2. Click "New +" and select "Web Service"
3. Connect your Git repository
4. Configure your service:
   - **Name**: report-system-laravel
   - **Region**: Choose the closest region to your users
   - **Branch**: Select your main branch (usually `main` or `master`)
   - **Root Directory**: / (root)
   - **Build Command**: `chmod +x deploy.sh && ./deploy.sh`
   - **Start Command**: `php -S 0.0.0.0:$PORT -t public/`
   - **Plan**: Free or Paid (as needed)
5. Click "Advanced" and add your environment variables
6. Click "Create Web Service"

#### Option 2: Using Render Blueprint (render.yaml)

1. Push your code to your Git repository
2. In Render Dashboard, click "New +" and select "Blueprint"
3. Connect your Git repository
4. Render will automatically detect the `render.yaml` file
5. Review the configuration and click "Apply"

### 4. Post-Deployment Steps

After successful deployment:

1. **Run Database Migrations**:
   ```bash
   # Connect to your Render instance via SSH
   render run --service your-service-name -- bash
   
   # Run migrations
   php artisan migrate --force
   
   # If you need to seed the database
   # php artisan db:seed --force
   ```

2. **Set Up Storage Link**:
   ```bash
   php artisan storage:link
   ```

3. **Cache Configuration (if needed)**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

### 5. Setting Up a Custom Domain (Optional)

1. In your Render dashboard, go to your web service
2. Click on "Settings"
3. Under "Custom Domains", click "Add Custom Domain"
4. Follow the instructions to verify domain ownership and configure DNS

### 6. Setting Up SSL (Automatic with Render)

Render automatically provisions SSL certificates for all production domains through Let's Encrypt. No additional setup is required.

## Troubleshooting

### Common Issues

1. **Application Key Not Set**
   - Ensure `APP_KEY` is set in your environment variables
   - You can generate one with: `php artisan key:generate --show`

2. **Database Connection Issues**
   - Verify your database credentials
   - Ensure your database is accessible from Render's IP addresses
   - Check if your database allows external connections

3. **Asset Loading Issues**
   - Make sure `ASSET_URL` is correctly set in your environment
   - Run `npm run build` if you're having issues with compiled assets

4. **Storage Permissions**
   - Ensure the storage directory is writable:
     ```bash
     chmod -R 775 storage/
     chmod -R 775 bootstrap/cache/
     ```

### Viewing Logs

You can view your application logs in the Render dashboard:
1. Go to your web service
2. Click on "Logs" in the sidebar

## Maintenance

### Updating Your Application

1. Push your changes to your Git repository
2. Render will automatically detect the changes and trigger a new deployment
3. Monitor the deployment in the Render dashboard

### Environment Variables

To update environment variables:
1. Go to your web service in the Render dashboard
2. Click on "Environment"
3. Add or update variables as needed
4. Click "Save Changes"

### Scaling

For increased traffic:
1. Go to your web service in the Render dashboard
2. Click on "Manual Scaling"
3. Adjust the number of instances as needed
4. Click "Save Changes"

## Support

For additional help, please contact your system administrator or refer to the [Render documentation](https://render.com/docs).
