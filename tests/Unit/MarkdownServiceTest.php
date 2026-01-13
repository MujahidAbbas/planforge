<?php

use App\Services\MarkdownService;

beforeEach(function () {
    $this->service = app(MarkdownService::class);
});

describe('MarkdownService', function () {
    it('renders basic markdown to HTML', function () {
        $markdown = '# Hello World';
        $html = $this->service->render($markdown);

        expect($html)->toContain('<h1>Hello World</h1>');
    });

    it('renders GFM tables', function () {
        $markdown = <<<'MD'
| Feature | Status |
|---------|--------|
| Tables  | Yes    |
MD;

        $html = $this->service->render($markdown);

        expect($html)->toContain('<table>');
        expect($html)->toContain('<th>Feature</th>');
        expect($html)->toContain('<td>Tables</td>');
    });

    it('renders GFM task lists', function () {
        $markdown = <<<'MD'
- [x] Completed task
- [ ] Pending task
MD;

        $html = $this->service->render($markdown);

        expect($html)->toContain('type="checkbox"');
        expect($html)->toContain('checked');
    });

    it('renders code blocks', function () {
        $markdown = <<<'MD'
```php
echo "Hello";
```
MD;

        $html = $this->service->render($markdown);

        expect($html)->toContain('<code');
        expect($html)->toContain('echo');
    });

    it('renders inline code', function () {
        $markdown = 'Use `composer install` to install.';
        $html = $this->service->render($markdown);

        expect($html)->toContain('<code>composer install</code>');
    });

    it('renders links', function () {
        $markdown = '[Laravel](https://laravel.com)';
        $html = $this->service->render($markdown);

        expect($html)->toContain('<a href="https://laravel.com">Laravel</a>');
    });

    it('returns empty string for empty input', function () {
        expect($this->service->render(''))->toBe('');
        expect($this->service->render('   '))->toBe('');
    });

    it('strips raw HTML for security', function () {
        $markdown = '<script>alert("xss")</script>';
        $html = $this->service->render($markdown);

        expect($html)->not->toContain('<script>');
    });

    it('strips dangerous HTML tags', function () {
        $markdown = '<iframe src="evil.com"></iframe>';
        $html = $this->service->render($markdown);

        expect($html)->not->toContain('<iframe');
    });

    it('renders strikethrough', function () {
        $markdown = '~~deleted~~';
        $html = $this->service->render($markdown);

        expect($html)->toContain('<del>deleted</del>');
    });

    it('renders autolinks', function () {
        $markdown = 'Visit https://example.com for more.';
        $html = $this->service->render($markdown);

        expect($html)->toContain('<a href="https://example.com">');
    });
});
