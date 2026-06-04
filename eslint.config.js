import antfu from '@antfu/eslint-config';

export default antfu(
    {
        vue: true,
        // This project indents with 4 spaces and keeps semicolons (matching the
        // existing Vue/JS style); override antfu's defaults so the linter doesn't
        // fight the codebase.
        stylistic: {
            indent: 4,
            semi: true,
        },
        ignores: [
            'public/**',
            'vendor/**',
            'bootstrap/ssr/**',
            'storage/**',
            'resources/js/types/generated.d.ts',
            '*.php',
            '**/*.md',
        ],
    },
    {
        rules: {
            // This codebase consistently emits kebab-case custom events and
            // mirrors backend snake_case prop names (branch_id, recent_activities,
            // employees_count); both are valid Vue conventions the project chose.
            'vue/custom-event-name-casing': 'off',
            'vue/prop-name-casing': 'off',
        },
    },
);
