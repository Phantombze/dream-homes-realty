#!/bin/bash
# Simple build script to minify CSS for production

echo "🔨 Building production assets..."

# Minify site.css
npx cssnano assets/css/site.css assets/css/site.min.css

# Minify admin.css
npx cssnano assets/css/admin.css assets/css/admin.min.css

echo "✅ Build complete!"
echo "📦 Minified files created:"
echo "   - assets/css/site.min.css"
echo "   - assets/css/admin.min.css"
echo ""
echo "🚀 Upload these .min.css files to your server"
