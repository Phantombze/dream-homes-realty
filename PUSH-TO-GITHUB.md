# Push to GitHub Instructions

## After creating the repository on GitHub, run these commands:

```bash
# Navigate to project directory
cd "c:\Users\IT\Desktop\Dream Homes"

# Add GitHub remote (replace USERNAME with your GitHub username)
git remote add origin https://github.com/PhantomBze/dream-homes.git

# Rename branch to main (modern standard)
git branch -M main

# Push to GitHub
git push -u origin main
```

## Authentication

When you push, GitHub will ask for credentials:

**Username:** PhantomBze
**Password:** [Use a Personal Access Token, NOT your GitHub password]

### Creating a Personal Access Token:

1. Go to: https://github.com/settings/tokens
2. Click "Generate new token" → "Generate new token (classic)"
3. Give it a name: "Dream Homes Local Development"
4. Select scopes:
   - ✅ repo (Full control of private repositories)
5. Click "Generate token"
6. **COPY THE TOKEN** (you won't see it again!)
7. Use this token as your password when git push asks

### Save Token (Optional but Recommended):

After first successful push, Git will offer to save credentials using Windows Credential Manager.
Click "Yes" to avoid re-entering the token every time.

---

## Verify Success

After pushing, visit:
https://github.com/PhantomBze/dream-homes

You should see your README.md displayed with all your files!

---

## Development Workflow (Using dev Branch)

### Daily Development Work:

Work on the `dev` branch for all changes and testing:

```bash
# Make sure you're on dev branch
git checkout dev

# Stage changes
git add .

# Commit with message
git commit -m "Description of changes"

# Push to GitHub dev branch
git push
```

### When Ready to Deploy to Production:

After testing changes on dev, merge to main:

```bash
# Switch to main branch
git checkout main

# Merge dev into main
git merge dev

# Push main to GitHub (production)
git push

# Switch back to dev for continued work
git checkout dev
```

### Branch Overview:

- **`dev`** - Development/testing branch (your daily work goes here)
- **`main`** - Production branch (only merge tested, stable code here)

This workflow lets you safely test changes before they go live! 🚀
