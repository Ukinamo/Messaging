# Deploy to Fly.io — Step by Step

## Prerequisites

- A [Fly.io](https://fly.io) account (free tier available)
- Your code pushed to GitHub or Bitbucket

---

## Step 1: Install Fly CLI

**Windows (PowerShell):**
```powershell
irm https://fly.io/install.ps1 | iex
```

**Mac/Linux:**
```bash
curl -L https://fly.io/install.sh | sh
```

After installing, restart your terminal.

---

## Step 2: Log in to Fly

```bash
flyctl auth login
```

A browser window opens. Sign up or log in, then return to the terminal.

---

## Step 3: Launch the app

Go to https://fly.io/dashboard and click **"Launch an App"**, then fill in:

| Setting | Value |
|---|---|
| **App name** | `messaging-app` (or any unique name) |
| **Organization** | Personal |
| **Branch** | `main` |
| **Region** | Pick the closest to you |
| **Internal port** | `8000` |
| **CPU** | shared-cpu-1x |
| **Memory** | 256MB |

### Environment Variables

Click **"+ New environment variable"** for each:

| Key | Value |
|---|---|
| `APP_KEY` | Run `php artisan key:generate --show` locally and paste the result |
| `APP_URL` | `https://YOUR-APP-NAME.fly.dev` |
| `REVERB_HOST` | `YOUR-APP-NAME.fly.dev` |
| `VITE_REVERB_HOST` | `YOUR-APP-NAME.fly.dev` |

> Replace `YOUR-APP-NAME` with the app name you chose above.

### Database

Leave **Managed Postgres** unchecked (the app uses SQLite).

### Paths

Leave Working directory and Config path as defaults (`./`).

Click **Deploy**.

---

## Step 4: Wait for build

The first deploy takes 5–10 minutes. It will:
1. Clone your repo
2. Build the Docker image (install PHP, Node, Composer, npm)
3. Build frontend assets with Vite
4. Start the Laravel server + Reverb WebSocket server

---

## Step 5: Create persistent storage (important!)

After the first deploy, the app needs a persistent volume for SQLite and uploaded files.
Run in your terminal:

```bash
flyctl volumes create data --region YOUR-REGION --size 1 --app YOUR-APP-NAME
```

Replace `YOUR-REGION` with your chosen region code (e.g., `ams`, `sjc`, `iad`) and `YOUR-APP-NAME` with your app name.

Then redeploy:

```bash
flyctl deploy --app YOUR-APP-NAME
```

---

## Step 6: Verify

Open `https://YOUR-APP-NAME.fly.dev` in your browser. You should see the login page.

---

## Updating the app

Every time you push to the `main` branch, you can redeploy with:

```bash
flyctl deploy --app YOUR-APP-NAME
```

Or enable auto-deploy from the Fly.io dashboard under your app settings.

---

## Useful commands

```bash
# Check app status
flyctl status --app YOUR-APP-NAME

# View logs
flyctl logs --app YOUR-APP-NAME

# Open a shell in the running container
flyctl ssh console --app YOUR-APP-NAME

# Run artisan commands remotely
flyctl ssh console --app YOUR-APP-NAME -C "php /app/artisan migrate:status"

# List your apps
flyctl apps list
```

---

## Troubleshooting

### Build fails with "no such file or directory: Dockerfile"
Make sure the Dockerfile is committed and pushed to your repo:
```bash
git add Dockerfile && git commit -m "Add Dockerfile" && git push
```

### App crashes on start
Check logs: `flyctl logs --app YOUR-APP-NAME`
Common issues:
- Missing `APP_KEY` → Add it in environment variables
- SQLite permission error → Create the volume (Step 5)

### WebSocket not connecting
Make sure `REVERB_HOST` and `VITE_REVERB_HOST` match your Fly.io URL (without `https://`).

### Free tier limits
- 3 shared VMs with 256MB RAM
- Apps sleep after 15 min of inactivity
- First request after sleep takes ~10 seconds to wake up
