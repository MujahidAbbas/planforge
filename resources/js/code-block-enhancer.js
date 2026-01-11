/**
 * Alpine.js component for enhancing code blocks in markdown preview.
 * Adds syntax highlighting, language labels, and copy buttons.
 */
export default function codeBlockEnhancer() {
    return {
        observer: null,

        init() {
            // Run enhancement after initial render
            this.$nextTick(() => this.enhance());

            // Use MutationObserver to re-enhance when DOM changes
            // This catches Livewire morphing, content updates, etc.
            const self = this;
            this.observer = new MutationObserver((mutations) => {
                // Check if code blocks were added or if content changed
                const needsEnhancement = mutations.some(mutation => {
                    // Check for added nodes containing code blocks
                    if (mutation.addedNodes.length > 0) {
                        return Array.from(mutation.addedNodes).some(node =>
                            node.nodeType === 1 && (
                                node.matches?.('pre') ||
                                node.querySelector?.('pre code')
                            )
                        );
                    }
                    // Check if mutation target is a pre element
                    if (mutation.target.matches?.('pre')) {
                        return true;
                    }
                    return false;
                });

                if (needsEnhancement) {
                    self.$nextTick(() => self.enhance());
                }
            });

            this.observer.observe(this.$el, {
                childList: true,
                subtree: true
            });
        },

        destroy() {
            if (this.observer) {
                this.observer.disconnect();
            }
        },

        enhance() {
            if (!window.hljs) return;

            const codeBlocks = this.$el.querySelectorAll('pre code');

            codeBlocks.forEach(codeElement => {
                const pre = codeElement.parentElement;
                if (!pre || pre.tagName !== 'PRE') return;

                // Check if header already exists (avoid duplicates)
                const existingHeader = pre.querySelector('.code-block-header');
                if (existingHeader) return;

                // Detect language from class
                const language = this.detectLanguage(codeElement);

                // Apply syntax highlighting (only if not already highlighted)
                if (!codeElement.classList.contains('hljs') && language !== 'plaintext') {
                    try {
                        window.hljs.highlightElement(codeElement);
                    } catch (e) {
                        // Fallback silently if highlighting fails
                    }
                }

                // Add header with language label and copy button
                this.addCodeBlockHeader(pre, codeElement, language);
            });
        },

        detectLanguage(codeElement) {
            const classes = codeElement.className.split(' ');
            const langClass = classes.find(c => c.startsWith('language-'));

            if (langClass) {
                return langClass.replace('language-', '');
            }

            // Check if it looks like ASCII art (no highlighting needed)
            const content = codeElement.textContent || '';
            if (this.looksLikeAsciiArt(content)) {
                return 'plaintext';
            }

            return 'plaintext';
        },

        looksLikeAsciiArt(content) {
            // ASCII art typically has lots of box-drawing characters or pipes/dashes
            const boxChars = /[┌┐└┘├┤┬┴┼│─|+\-=]/g;
            const matches = content.match(boxChars) || [];
            const ratio = matches.length / content.length;

            // If more than 5% of content is box-drawing chars, treat as ASCII art
            return ratio > 0.05;
        },

        addCodeBlockHeader(pre, codeElement, language) {
            // Make pre relative for positioning
            pre.style.position = 'relative';

            // Create header container
            const header = document.createElement('div');
            header.className = 'code-block-header';

            // Language label
            const langLabel = document.createElement('span');
            langLabel.className = 'code-language';
            langLabel.textContent = this.formatLanguageName(language);

            // Copy button
            const copyBtn = document.createElement('button');
            copyBtn.className = 'copy-btn';
            copyBtn.type = 'button';
            copyBtn.textContent = 'Copy';

            // Store code content for copying (get original text before highlighting)
            const codeContent = codeElement.textContent;

            copyBtn.addEventListener('click', async () => {
                try {
                    // Try modern clipboard API first (requires HTTPS)
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(codeContent);
                    } else {
                        // Fallback for non-secure contexts (HTTP)
                        const textArea = document.createElement('textarea');
                        textArea.value = codeContent;
                        textArea.style.position = 'fixed';
                        textArea.style.left = '-9999px';
                        textArea.style.top = '-9999px';
                        document.body.appendChild(textArea);
                        textArea.focus();
                        textArea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textArea);
                    }

                    copyBtn.textContent = 'Copied!';
                    copyBtn.classList.add('copied');

                    setTimeout(() => {
                        copyBtn.textContent = 'Copy';
                        copyBtn.classList.remove('copied');
                    }, 2000);
                } catch (err) {
                    copyBtn.textContent = 'Failed';
                    setTimeout(() => {
                        copyBtn.textContent = 'Copy';
                    }, 2000);
                }
            });

            // Assemble header
            header.appendChild(langLabel);
            header.appendChild(copyBtn);

            // Insert header before code
            pre.insertBefore(header, pre.firstChild);
        },

        formatLanguageName(language) {
            const nameMap = {
                'js': 'JavaScript',
                'javascript': 'JavaScript',
                'ts': 'TypeScript',
                'typescript': 'TypeScript',
                'php': 'PHP',
                'py': 'Python',
                'python': 'Python',
                'sql': 'SQL',
                'bash': 'Bash',
                'shell': 'Shell',
                'sh': 'Shell',
                'json': 'JSON',
                'xml': 'XML',
                'html': 'HTML',
                'css': 'CSS',
                'yaml': 'YAML',
                'yml': 'YAML',
                'md': 'Markdown',
                'markdown': 'Markdown',
                'plaintext': 'Plain Text',
                'text': 'Plain Text'
            };

            return nameMap[language] || language.toUpperCase();
        }
    };
}

// Register as Alpine component
document.addEventListener('alpine:init', () => {
    Alpine.data('codeBlockEnhancer', codeBlockEnhancer);
});

// Re-enhance after Livewire fully initializes (handles initial hydration)
document.addEventListener('livewire:init', () => {
    // Small delay to ensure DOM is fully ready after hydration
    setTimeout(() => {
        if (window.hljs) {
            document.querySelectorAll('.markdown-preview pre code').forEach(codeElement => {
                const pre = codeElement.parentElement;
                if (pre && !pre.querySelector('.code-block-header')) {
                    // Trigger re-initialization of Alpine components
                    const alpineEl = pre.closest('[x-data]');
                    if (alpineEl && alpineEl.__x) {
                        alpineEl.__x.$data.enhance?.();
                    }
                }
            });
        }
    }, 50);
});

// Also run enhancement globally after Livewire page navigation
// This catches cases where the Alpine component might be destroyed/recreated
document.addEventListener('livewire:navigated', () => {
    setTimeout(() => {
        // Directly enhance any unprocessed code blocks
        if (window.hljs) {
            document.querySelectorAll('.markdown-preview pre code').forEach(codeElement => {
                const pre = codeElement.parentElement;
                if (pre && !pre.querySelector('.code-block-header')) {
                    // Apply highlighting if not already done
                    if (!codeElement.classList.contains('hljs')) {
                        try {
                            window.hljs.highlightElement(codeElement);
                        } catch (e) {
                            // Silently fail
                        }
                    }
                }
            });
        }
    }, 100);
});
