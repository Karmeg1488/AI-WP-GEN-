# AI WP GEN - Debug Logging Guide

## 📋 Viewing Logs in WordPress Admin Panel

After updating the plugin, you can now view debug logs directly in the WordPress admin panel without accessing the file system.

### How to view logs:

1. **Login to WordPress admin panel** - Go to your WordPress dashboard
2. **Navigate to AI WP GEN** - Click on "AI WP GEN" in the left sidebar
3. **Click on "Logs" tab** - You'll see the logs tab next to "Settings" and "Generation"
4. **View the logs** - All plugin operations will be logged with timestamps and detailed messages

### Log Information Includes:

- ✅ **Author creation status** - Shows when authors are created or if there are issues
- ✅ **OpenAI API communication** - Logs API requests, responses, and any errors
- ✅ **Article generation** - Shows which articles were created and content length
- ✅ **Image generation** - Logs image generation attempts and results
- ✅ **Error details** - Detailed error messages to help troubleshoot issues

### Clearing Logs:

To clear all logs (useful if the list gets too long):
1. Go to AI WP GEN admin panel
2. Click on "Logs" tab
3. Click the red "Clear Logs" button

## 🔧 What to Look For When Debugging:

### Authors Not Creating:
- Check logs for: "Initial author names from OpenAI"
- Look for: "Author created:" messages
- If you see "JSON decode failed" - the API response format issue must be fixed

### Articles Coming Out Empty:
- Check logs for: "Article content generated successfully. Content length:"
- If content length is very small (< 100 chars) - increase OpenAI API response timeout
- Look for: "Failed to generate article content after 3 attempts"

### API Errors:
- Look for lines starting with "API Status" or "API Error"
- These will show which HTTP status code was returned
- Rate limiting (429) will show automatic retries

## 📝 Troubleshooting Common Issues:

### "Failed to retrieve or create authors"
1. Check the "Logs" tab for detailed error messages
2. Ensure your OpenAI API key is valid
3. Check if author role exists in WordPress

### Empty articles in database
1. Look for content length in logs
2. If content length < 50 chars, the API is returning too little text
3. Increase max_tokens value or check input prompt

### Slow generation
1. API sometimes takes time to respond
2. Check logs for retry messages - these are expected
3. Each retry adds 2-3 seconds delay

## 💾 Log Storage:

- Logs are stored in WordPress options table
- Latest 100 log entries are kept (older ones are automatically deleted)
- Logs persist even if plugin is deactivated
- Clear logs using the button in admin panel

## 🚀 Next Steps:

When you run a generation task:
1. Check the logs immediately after
2. Search for "ERROR" or "Failed" to find issues
3. Look at timestamps to understand processing order
4. Share these logs if you need help debugging
