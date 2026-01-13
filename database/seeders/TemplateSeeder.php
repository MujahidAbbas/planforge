<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Enums\TemplateCategory;
use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = $this->getTemplates();

        foreach ($templates as $templateData) {
            Template::firstOrCreate(
                [
                    'name' => $templateData['name'],
                    'document_type' => $templateData['document_type'],
                ],
                $templateData
            );
        }
    }

    private function getTemplates(): array
    {
        return [
            // =====================================================
            // CORE TEMPLATES
            // =====================================================
            [
                'name' => 'PlanForge PRD',
                'description' => 'The default PlanForge template for writing detailed PRDs. Includes goals, context, user stories, requirements, and success metrics.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Core,
                'is_built_in' => true,
                'sort_order' => 1,
                'sections' => [
                    ['title' => 'Overview', 'description' => 'Brief summary of what we\'re building and why', 'required' => true],
                    ['title' => 'Problem Statement', 'description' => 'The user pain point or business need we\'re solving', 'required' => true],
                    ['title' => 'Goals & Success Metrics', 'description' => 'Measurable objectives and KPIs', 'required' => true],
                    ['title' => 'User Stories', 'description' => 'As a [user], I want [goal] so that [benefit]', 'required' => true],
                    ['title' => 'Functional Requirements', 'description' => 'What the product must do', 'required' => true],
                    ['title' => 'Non-Functional Requirements', 'description' => 'Performance, security, scalability', 'required' => false],
                    ['title' => 'Out of Scope', 'description' => 'What this feature will NOT do', 'required' => false],
                    ['title' => 'Open Questions', 'description' => 'Unresolved items needing discussion', 'required' => false],
                ],
            ],
            [
                'name' => 'No Template',
                'description' => 'Start from scratch — use your own judgment for content and structure.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Core,
                'is_built_in' => true,
                'sort_order' => 2,
                'sections' => [],
                'ai_instructions' => 'Generate a PRD based on best practices. Use your judgment on structure.',
            ],

            // =====================================================
            // TECHNICAL & PRODUCT DOCUMENTATION
            // =====================================================
            [
                'name' => 'API Documentation',
                'description' => 'Comprehensive guide for documenting APIs — covering purpose, architecture, authentication, error handling, and endpoints.',
                'document_type' => DocumentType::Tech,
                'category' => TemplateCategory::Technical,
                'is_built_in' => true,
                'sort_order' => 10,
                'sections' => [
                    [
                        'title' => 'Overview',
                        'description' => 'High-level summary of the API including purpose, core functionalities, and architecture.',
                        'required' => true,
                        'guidance' => 'Provide a clear understanding of what the API does and who it\'s for.',
                    ],
                    [
                        'title' => 'API Purpose',
                        'description' => 'What the API is designed to do and key problems it solves.',
                        'required' => true,
                        'guidance' => 'Clearly articulate primary use cases and mention target developers.',
                    ],
                    [
                        'title' => 'Core Functionalities',
                        'description' => 'Main features and unique capabilities of the API.',
                        'required' => true,
                        'guidance' => 'List features, highlight advanced functionalities, provide examples of what can be achieved.',
                    ],
                    [
                        'title' => 'Architecture Overview',
                        'description' => 'Brief overview of API architecture and dependencies.',
                        'required' => false,
                        'guidance' => 'Note scalability considerations, core components, and any external dependencies.',
                    ],
                    [
                        'title' => 'API Authentication',
                        'description' => 'How users authenticate with the API, including security best practices.',
                        'required' => true,
                        'guidance' => 'Include detailed information on required API keys, tokens, or secrets.',
                    ],
                    [
                        'title' => 'Authentication Methods',
                        'description' => 'Supported authentication methods (API keys, OAuth, JWT).',
                        'required' => true,
                        'guidance' => 'Provide step-by-step instructions for each method and include code snippets for common programming languages.',
                    ],
                    [
                        'title' => 'Token Management',
                        'description' => 'How to obtain and refresh tokens.',
                        'required' => true,
                        'guidance' => 'Explain token expiration policies and best practices for secure token storage.',
                    ],
                    [
                        'title' => 'Common Issues and Troubleshooting',
                        'description' => 'Common authentication errors and their solutions.',
                        'required' => false,
                        'guidance' => 'List common errors, debugging tips, and links to additional support resources.',
                    ],
                    [
                        'title' => 'Error Messages',
                        'description' => 'All possible error codes and messages with explanations.',
                        'required' => true,
                        'guidance' => 'List all error codes that the API might return with troubleshooting steps.',
                    ],
                    [
                        'title' => 'Error Code List',
                        'description' => 'Table of all error codes with corresponding messages and descriptions.',
                        'required' => true,
                        'guidance' => 'Group by category (client-side 4xx, server-side 5xx). Include HTTP status and message.',
                    ],
                    [
                        'title' => 'API Endpoints and Operations',
                        'description' => 'Detailed descriptions of all available endpoints.',
                        'required' => true,
                        'guidance' => 'Document HTTP methods, expected inputs and outputs, and usage examples.',
                    ],
                ],
            ],
            [
                'name' => 'Technical Design Document',
                'description' => 'Structured template for technical design including architecture, components, and trade-offs.',
                'document_type' => DocumentType::Tech,
                'category' => TemplateCategory::Technical,
                'is_built_in' => true,
                'sort_order' => 11,
                'sections' => [
                    ['title' => 'Overview', 'description' => 'Technical summary and goals', 'required' => true],
                    ['title' => 'Architecture', 'description' => 'System design with diagrams', 'required' => true],
                    ['title' => 'Components', 'description' => 'Key components and responsibilities', 'required' => true],
                    ['title' => 'Data Model', 'description' => 'Database schema and relationships', 'required' => true],
                    ['title' => 'Dependencies', 'description' => 'External services and libraries', 'required' => false],
                    ['title' => 'Trade-offs & Decisions', 'description' => 'Key technical decisions and rationale', 'required' => true],
                    ['title' => 'Security Considerations', 'description' => 'Auth, data protection, vulnerabilities', 'required' => false],
                    ['title' => 'Testing Strategy', 'description' => 'Unit, integration, E2E approach', 'required' => false],
                ],
            ],
            [
                'name' => 'Product Security Assessment',
                'description' => 'Evaluate and strengthen product security with specs, risks, and mitigation strategies.',
                'document_type' => DocumentType::Tech,
                'category' => TemplateCategory::Technical,
                'is_built_in' => true,
                'sort_order' => 12,
                'sections' => [
                    ['title' => 'Product Overview', 'description' => 'What the product does', 'required' => true],
                    ['title' => 'Technical Specifications', 'description' => 'Tech stack, infrastructure, data flows', 'required' => true],
                    ['title' => 'Key Features', 'description' => 'Features with security implications', 'required' => true],
                    ['title' => 'Threat Model', 'description' => 'Potential attack vectors and threats', 'required' => true],
                    ['title' => 'Security Risks', 'description' => 'Identified vulnerabilities and severity', 'required' => true],
                    ['title' => 'Mitigation Strategies', 'description' => 'How to address each risk', 'required' => true],
                    ['title' => 'Compliance Requirements', 'description' => 'GDPR, SOC2, HIPAA considerations', 'required' => false],
                ],
            ],

            // =====================================================
            // PRODUCT PLANNING & STRATEGY
            // =====================================================
            [
                'name' => 'Product Strategy Document',
                'description' => 'Define product vision, market context, goals, strategies, and roadmap.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Strategy,
                'is_built_in' => true,
                'sort_order' => 20,
                'sections' => [
                    ['title' => 'Vision', 'description' => 'Long-term product vision (3-5 years)', 'required' => true],
                    ['title' => 'Market Context', 'description' => 'Market size, trends, and opportunity', 'required' => true],
                    ['title' => 'Target Audience', 'description' => 'Primary and secondary user segments', 'required' => true],
                    ['title' => 'Goals & OKRs', 'description' => 'Quarterly and annual objectives', 'required' => true],
                    ['title' => 'Strategy', 'description' => 'How we\'ll achieve the vision', 'required' => true],
                    ['title' => 'Roadmap', 'description' => 'High-level timeline and milestones', 'required' => true],
                    ['title' => 'Competitive Positioning', 'description' => 'How we differentiate', 'required' => false],
                ],
            ],
            [
                'name' => 'Release Plan',
                'description' => 'Plan and execute product releases with milestones, dependencies, and success metrics.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Strategy,
                'is_built_in' => true,
                'sort_order' => 21,
                'sections' => [
                    ['title' => 'Release Overview', 'description' => 'What\'s included in this release', 'required' => true],
                    ['title' => 'Goals', 'description' => 'What we aim to achieve', 'required' => true],
                    ['title' => 'Features & Changes', 'description' => 'Detailed list of included features', 'required' => true],
                    ['title' => 'Milestones', 'description' => 'Key dates and checkpoints', 'required' => true],
                    ['title' => 'Dependencies', 'description' => 'External teams, services, or blockers', 'required' => false],
                    ['title' => 'Rollout Plan', 'description' => 'Staged rollout or big bang?', 'required' => true],
                    ['title' => 'Communication Plan', 'description' => 'How to announce to users', 'required' => false],
                    ['title' => 'Success Metrics', 'description' => 'How we measure release success', 'required' => true],
                ],
            ],
            [
                'name' => 'Go-to-Market Plan',
                'description' => 'Strategic template for product launch across marketing, sales, and operations.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Strategy,
                'is_built_in' => true,
                'sort_order' => 22,
                'sections' => [
                    ['title' => 'Executive Summary', 'description' => 'One paragraph GTM overview', 'required' => true],
                    ['title' => 'Target Market', 'description' => 'Who we\'re selling to', 'required' => true],
                    ['title' => 'Value Proposition', 'description' => 'Why customers should care', 'required' => true],
                    ['title' => 'Competitive Landscape', 'description' => 'Key competitors and positioning', 'required' => true],
                    ['title' => 'Marketing Strategy', 'description' => 'Channels, messaging, campaigns', 'required' => true],
                    ['title' => 'Sales Strategy', 'description' => 'Sales process and enablement', 'required' => false],
                    ['title' => 'Launch Timeline', 'description' => 'Pre-launch, launch day, post-launch', 'required' => true],
                    ['title' => 'Budget', 'description' => 'Marketing and sales spend', 'required' => false],
                    ['title' => 'Success Metrics', 'description' => 'GTM KPIs to track', 'required' => true],
                ],
            ],
            [
                'name' => 'Product Launch Checklist',
                'description' => 'Ensure smooth launches with technical, marketing, and operational readiness checks.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Strategy,
                'is_built_in' => true,
                'sort_order' => 23,
                'sections' => [
                    ['title' => 'Technical Readiness', 'description' => 'Code complete, tested, deployed', 'required' => true],
                    ['title' => 'Documentation', 'description' => 'User docs, API docs, release notes', 'required' => true],
                    ['title' => 'Marketing Readiness', 'description' => 'Messaging, assets, campaigns ready', 'required' => true],
                    ['title' => 'Sales Readiness', 'description' => 'Training, collateral, pricing', 'required' => false],
                    ['title' => 'Support Readiness', 'description' => 'Help docs, CS training, escalation', 'required' => true],
                    ['title' => 'Legal/Compliance', 'description' => 'ToS updates, privacy review', 'required' => false],
                    ['title' => 'Monitoring & Alerts', 'description' => 'Dashboards and on-call rotation', 'required' => true],
                    ['title' => 'Rollback Plan', 'description' => 'How to revert if things go wrong', 'required' => true],
                ],
            ],
            [
                'name' => 'OKRs',
                'description' => 'Generate and align team Objectives and Key Results around product goals.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Strategy,
                'is_built_in' => true,
                'sort_order' => 24,
                'sections' => [
                    ['title' => 'Context', 'description' => 'Current situation and why these OKRs', 'required' => true],
                    ['title' => 'Objective 1', 'description' => 'Qualitative goal with 3-5 Key Results', 'required' => true],
                    ['title' => 'Objective 2', 'description' => 'Qualitative goal with 3-5 Key Results', 'required' => false],
                    ['title' => 'Objective 3', 'description' => 'Qualitative goal with 3-5 Key Results', 'required' => false],
                    ['title' => 'Dependencies', 'description' => 'Cross-team dependencies', 'required' => false],
                    ['title' => 'Risks', 'description' => 'What could prevent success', 'required' => false],
                ],
                'ai_instructions' => 'Key Results should be measurable with specific numbers. Use the SMART framework.',
            ],
            [
                'name' => 'PR FAQ',
                'description' => 'Amazon-style "Working Backwards" template with mock press release and FAQs.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Strategy,
                'is_built_in' => true,
                'sort_order' => 25,
                'sections' => [
                    ['title' => 'Press Release', 'description' => 'Written as if announcing a successful launch', 'required' => true],
                    ['title' => 'Customer Problem', 'description' => 'What problem does this solve?', 'required' => true],
                    ['title' => 'Solution', 'description' => 'How does the product solve it?', 'required' => true],
                    ['title' => 'Customer Quote', 'description' => 'Fictional quote from happy customer', 'required' => false],
                    ['title' => 'Getting Started', 'description' => 'How customers will use it', 'required' => true],
                    ['title' => 'Internal FAQs', 'description' => 'Questions stakeholders will ask', 'required' => true],
                    ['title' => 'External FAQs', 'description' => 'Questions customers will ask', 'required' => true],
                ],
                'ai_instructions' => 'Write the press release from the future, as if the product has already launched successfully.',
            ],
            [
                'name' => 'PRD for v0.dev',
                'description' => 'Optimized format for v0.dev and AI development platforms — goals, UX, and functionality.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Strategy,
                'is_built_in' => true,
                'sort_order' => 26,
                'sections' => [
                    ['title' => 'Product Overview', 'description' => 'What are we building?', 'required' => true],
                    ['title' => 'User Experience', 'description' => 'Key screens and user flows', 'required' => true],
                    ['title' => 'Core Functionality', 'description' => 'Must-have features for MVP', 'required' => true],
                    ['title' => 'UI Components', 'description' => 'Key UI elements and patterns', 'required' => true],
                    ['title' => 'Data Requirements', 'description' => 'What data to display/capture', 'required' => true],
                    ['title' => 'Design Guidelines', 'description' => 'Style, colors, typography', 'required' => false],
                    ['title' => 'Success Metrics', 'description' => 'How to measure success', 'required' => true],
                ],
                'ai_instructions' => 'Focus on visual and interactive elements. Be specific about UI components. Output should be directly usable with v0.dev prompts.',
            ],

            // =====================================================
            // RESEARCH, TESTING & UX
            // =====================================================
            [
                'name' => 'Usability Test Plan',
                'description' => 'Plan usability studies with objectives, methodology, and metrics.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Research,
                'is_built_in' => true,
                'sort_order' => 30,
                'sections' => [
                    ['title' => 'Objectives', 'description' => 'What we want to learn', 'required' => true],
                    ['title' => 'Research Questions', 'description' => 'Specific questions to answer', 'required' => true],
                    ['title' => 'Methodology', 'description' => 'Moderated vs unmoderated, remote vs in-person', 'required' => true],
                    ['title' => 'Participants', 'description' => 'Recruitment criteria and sample size', 'required' => true],
                    ['title' => 'Test Environment', 'description' => 'Tools, prototype, or production', 'required' => true],
                    ['title' => 'Tasks', 'description' => 'What participants will do', 'required' => true],
                    ['title' => 'Metrics', 'description' => 'Success rate, time on task, SUS score', 'required' => true],
                    ['title' => 'Timeline', 'description' => 'Schedule and milestones', 'required' => false],
                ],
            ],
            [
                'name' => 'User Testing Plan',
                'description' => 'Validate features and UX quality through structured user testing.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Research,
                'is_built_in' => true,
                'sort_order' => 31,
                'sections' => [
                    ['title' => 'Test Objectives', 'description' => 'What we\'re validating', 'required' => true],
                    ['title' => 'Feature Scope', 'description' => 'Which features to test', 'required' => true],
                    ['title' => 'Test Scenarios', 'description' => 'Real-world scenarios to test', 'required' => true],
                    ['title' => 'Participant Criteria', 'description' => 'Who should participate', 'required' => true],
                    ['title' => 'Success Criteria', 'description' => 'What constitutes a pass/fail', 'required' => true],
                    ['title' => 'Feedback Collection', 'description' => 'How to capture feedback', 'required' => true],
                    ['title' => 'Analysis Plan', 'description' => 'How to analyze results', 'required' => false],
                ],
            ],
            [
                'name' => 'User Personas',
                'description' => 'Detailed profiles of target users to guide design and decisions.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Research,
                'is_built_in' => true,
                'sort_order' => 32,
                'sections' => [
                    ['title' => 'Persona Overview', 'description' => 'Name, role, demographics', 'required' => true],
                    ['title' => 'Goals', 'description' => 'What they\'re trying to achieve', 'required' => true],
                    ['title' => 'Pain Points', 'description' => 'Current frustrations', 'required' => true],
                    ['title' => 'Behaviors', 'description' => 'How they work/think today', 'required' => true],
                    ['title' => 'Motivations', 'description' => 'What drives their decisions', 'required' => true],
                    ['title' => 'Quote', 'description' => 'A representative statement', 'required' => false],
                    ['title' => 'Scenarios', 'description' => 'Day-in-the-life context', 'required' => false],
                ],
                'ai_instructions' => 'Create 2-3 distinct personas. Make them feel like real people with specific details.',
            ],
            [
                'name' => 'Customer Journey Map',
                'description' => 'Map customer experience from awareness to advocacy, identifying pain points.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Research,
                'is_built_in' => true,
                'sort_order' => 33,
                'sections' => [
                    ['title' => 'Persona', 'description' => 'Which user this journey represents', 'required' => true],
                    ['title' => 'Awareness Stage', 'description' => 'How they discover the product', 'required' => true],
                    ['title' => 'Consideration Stage', 'description' => 'How they evaluate options', 'required' => true],
                    ['title' => 'Decision Stage', 'description' => 'What drives purchase/signup', 'required' => true],
                    ['title' => 'Onboarding Stage', 'description' => 'First experience with product', 'required' => true],
                    ['title' => 'Usage Stage', 'description' => 'Ongoing product experience', 'required' => true],
                    ['title' => 'Advocacy Stage', 'description' => 'How they become promoters', 'required' => false],
                    ['title' => 'Pain Points & Opportunities', 'description' => 'Issues and improvement areas', 'required' => true],
                ],
            ],
            [
                'name' => 'Accessibility Compliance Checklist',
                'description' => 'Ensure accessibility standards for design, readability, and navigation.',
                'document_type' => DocumentType::Tech,
                'category' => TemplateCategory::Research,
                'is_built_in' => true,
                'sort_order' => 34,
                'sections' => [
                    ['title' => 'Visual Design', 'description' => 'Color contrast, font sizes, icons', 'required' => true],
                    ['title' => 'Keyboard Navigation', 'description' => 'Full keyboard support, focus states', 'required' => true],
                    ['title' => 'Screen Reader Support', 'description' => 'ARIA labels, semantic HTML', 'required' => true],
                    ['title' => 'Forms & Inputs', 'description' => 'Labels, error messages, validation', 'required' => true],
                    ['title' => 'Media', 'description' => 'Alt text, captions, transcripts', 'required' => true],
                    ['title' => 'Motion & Animation', 'description' => 'Reduced motion support', 'required' => false],
                    ['title' => 'Testing', 'description' => 'Tools and manual testing plan', 'required' => true],
                    ['title' => 'WCAG Compliance Level', 'description' => 'Target A, AA, or AAA', 'required' => true],
                ],
            ],

            // =====================================================
            // ANALYSIS & REPORTING
            // =====================================================
            [
                'name' => 'Competitive Analysis Report',
                'description' => 'Compare competitors on features, positioning, and strategic insights.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Analysis,
                'is_built_in' => true,
                'sort_order' => 40,
                'sections' => [
                    ['title' => 'Executive Summary', 'description' => 'Key findings and recommendations', 'required' => true],
                    ['title' => 'Competitors Analyzed', 'description' => 'List of competitors and selection criteria', 'required' => true],
                    ['title' => 'Company Profiles', 'description' => 'Background, funding, market position', 'required' => true],
                    ['title' => 'Feature Comparison', 'description' => 'Side-by-side feature matrix', 'required' => true],
                    ['title' => 'Pricing Analysis', 'description' => 'Pricing models and comparison', 'required' => true],
                    ['title' => 'Strengths & Weaknesses', 'description' => 'SWOT for each competitor', 'required' => true],
                    ['title' => 'Market Positioning', 'description' => 'How each positions themselves', 'required' => false],
                    ['title' => 'Strategic Insights', 'description' => 'Opportunities and threats for us', 'required' => true],
                ],
            ],

            // =====================================================
            // COMMUNITY TEMPLATES
            // =====================================================
            [
                'name' => 'Aakash\'s PRD',
                'description' => 'Aakash Gupta\'s popular PRD framework focused on clarity and actionability.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Community,
                'is_built_in' => true,
                'is_community' => true,
                'author' => 'Aakash Gupta',
                'sort_order' => 50,
                'sections' => [
                    ['title' => 'Problem', 'description' => 'What problem are we solving?', 'required' => true],
                    ['title' => 'Audience', 'description' => 'Who has this problem?', 'required' => true],
                    ['title' => 'Why Now?', 'description' => 'Why is this the right time?', 'required' => true],
                    ['title' => 'Solution', 'description' => 'What are we building?', 'required' => true],
                    ['title' => 'Business Impact', 'description' => 'How does this help the business?', 'required' => true],
                    ['title' => 'Success Metrics', 'description' => 'How do we measure success?', 'required' => true],
                    ['title' => 'Scope', 'description' => 'What\'s in and out of scope?', 'required' => true],
                    ['title' => 'Milestones', 'description' => 'Key delivery dates', 'required' => false],
                ],
            ],
            [
                'name' => 'Peter\'s PRD',
                'description' => 'Peter Yang\'s PRD template emphasizing storytelling and user-centric design.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Community,
                'is_built_in' => true,
                'is_community' => true,
                'author' => 'Peter Yang',
                'sort_order' => 51,
                'sections' => [
                    ['title' => 'The Story', 'description' => 'User narrative that illustrates the problem', 'required' => true],
                    ['title' => 'Problem Definition', 'description' => 'Clear problem statement', 'required' => true],
                    ['title' => 'User Goals', 'description' => 'What users want to achieve', 'required' => true],
                    ['title' => 'Proposed Solution', 'description' => 'How we solve the problem', 'required' => true],
                    ['title' => 'User Journey', 'description' => 'Step-by-step user flow', 'required' => true],
                    ['title' => 'Success Metrics', 'description' => 'Measurable outcomes', 'required' => true],
                    ['title' => 'Risks & Mitigations', 'description' => 'What could go wrong', 'required' => false],
                ],
                'ai_instructions' => 'Start with a compelling user story. Make it feel personal and relatable.',
            ],
            [
                'name' => 'Lenny\'s 1-Pager',
                'description' => 'Lenny Rachitsky\'s concise one-pager for quick team alignment.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Community,
                'is_built_in' => true,
                'is_community' => true,
                'author' => 'Lenny Rachitsky',
                'sort_order' => 52,
                'sections' => [
                    ['title' => 'Problem', 'description' => 'What problem are we solving? (1-2 sentences)', 'required' => true],
                    ['title' => 'Solution', 'description' => 'What are we building? (1-2 sentences)', 'required' => true],
                    ['title' => 'Why Now?', 'description' => 'Why is this important now?', 'required' => true],
                    ['title' => 'Success Looks Like', 'description' => 'How we know we succeeded', 'required' => true],
                    ['title' => 'Key Risks', 'description' => 'Top 2-3 risks', 'required' => false],
                ],
                'ai_instructions' => 'Keep it extremely concise. This should fit on one page. Prioritize clarity over completeness.',
            ],
            [
                'name' => 'Founding Hypothesis',
                'description' => 'Jake Knapp & John Zeratsky framework for aligning on customer, problem, and differentiation.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Community,
                'is_built_in' => true,
                'is_community' => true,
                'author' => 'Jake Knapp & John Zeratsky',
                'sort_order' => 53,
                'sections' => [
                    ['title' => 'Customer', 'description' => 'Who is our target customer?', 'required' => true],
                    ['title' => 'Problem', 'description' => 'What problem do they have?', 'required' => true],
                    ['title' => 'Insight', 'description' => 'What unique insight do we have?', 'required' => true],
                    ['title' => 'Solution', 'description' => 'How do we solve it?', 'required' => true],
                    ['title' => 'Differentiation', 'description' => 'Why are we uniquely positioned?', 'required' => true],
                    ['title' => 'Business Model', 'description' => 'How do we make money?', 'required' => true],
                ],
                'ai_instructions' => 'Focus on clarity and conviction. Each section should be 2-3 sentences max.',
            ],
            [
                'name' => 'App Prototyping with AI',
                'description' => 'Tailored for v0.dev, Bolt.new, Loveable, and Replit — app stack and requirements.',
                'document_type' => DocumentType::Prd,
                'category' => TemplateCategory::Community,
                'is_built_in' => true,
                'is_community' => true,
                'author' => 'Community',
                'sort_order' => 54,
                'sections' => [
                    ['title' => 'Product Overview', 'description' => 'What are we building in one paragraph?', 'required' => true],
                    ['title' => 'Target Platform', 'description' => 'v0.dev, Bolt.new, Replit, etc.', 'required' => true],
                    ['title' => 'App Stack Defaults', 'description' => 'React, Next.js, Tailwind, etc.', 'required' => true],
                    ['title' => 'Core Features', 'description' => 'Must-have functionality', 'required' => true],
                    ['title' => 'UI/UX Requirements', 'description' => 'Screens, components, interactions', 'required' => true],
                    ['title' => 'Backend Requirements', 'description' => 'API, database, auth needs', 'required' => false],
                    ['title' => 'Sample Data', 'description' => 'Mock data for prototyping', 'required' => false],
                ],
                'ai_instructions' => 'Be very specific about UI components and interactions. Include example data. Output should be paste-ready for AI code generation tools.',
            ],
        ];
    }
}
