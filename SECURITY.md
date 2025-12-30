# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 0.x.x   | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability in PlanForge, please report it responsibly:

1. **Do NOT** open a public GitHub issue
2. Email the maintainers directly (add your email here)
3. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Any suggested fixes

## Response Timeline

- **Initial Response**: Within 48 hours
- **Status Update**: Within 7 days
- **Fix Timeline**: Depends on severity

## Security Considerations

### API Keys

- Never commit API keys to the repository
- Use `.env` for all secrets
- The `.env` file is gitignored by default

### User Input

- All user input is treated as untrusted
- AI prompts use template separation (instructions vs data)
- File uploads should be validated and sanitized

### AI/LLM Security

- Prompt injection protections are implemented
- User-provided content is never directly embedded in system prompts
- Rate limiting prevents abuse

## Known Limitations

- Authentication is not yet implemented (coming soon)
- Project authorization is disabled pending auth implementation

## Security Best Practices for Deployment

1. Use HTTPS in production
2. Set `APP_DEBUG=false` in production
3. Use strong, unique `APP_KEY`
4. Configure proper CORS settings
5. Use Redis with authentication for queues in production
