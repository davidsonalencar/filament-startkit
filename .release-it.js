const fs = require('fs');

function removePrereleaseSectionsFromChangelog() {
    const file = 'CHANGELOG.md';

    if (!fs.existsSync(file)) {
        return;
    }

    const content = fs.readFileSync(file, 'utf8');

    const cleaned = content
        .replace(
            /^##\s+\[[^\]]+-pr\.\d+\][\s\S]*?(?=^##\s+\[|^##\s+|$)/gm,
            ''
        )
        .replace(/\n{3,}/g, '\n\n')
        .trimEnd() + '\n';

    fs.writeFileSync(file, cleaned);
}

module.exports = {
    git: {
        commitMessage: 'release: release v${version}',
        tagName: 'v${version}',
        requireCleanWorkingDir: false,
        addUntrackedFiles: true,
    },

    github: {
        release: true,
    },

    npm: false,

    hooks: {
        'after:bump': [
            'vendor/bin/envoy run release:stack --tag=${version}',
            'echo ">> SAAAAAAAAA"',
            
        ],
    },

    plugins: {
        '@release-it/bumper': {
            in: 'VERSION',
            out: 'VERSION',
        },

        '@release-it/conventional-changelog': {
            infile: 'CHANGELOG.md',
            header: 'XXXXXXXXXX',
            preset: {
                name: 'conventionalcommits',
                types: [
                    {
                        type: 'feat',
                        section: 'Nova Funcionalidade',
                    },
                    {
                        type: 'fix',
                        section: 'Correção de Bug',
                    },
                    {
                        type: 'docs',
                        hidden: true,
                    },
                    {
                        type: 'style',
                        section: 'Estilo e Formatação',
                    },
                    {
                        type: 'refactor',
                        hidden: true,
                    },
                    {
                        type: 'test',
                        hidden: true,
                    },
                    {
                        type: 'chore',
                        "section": "Outras Melhorias"
                    },
                    {
                        type: 'build',
                        hidden: true,
                    },
                    {
                        type: 'ci',
                        hidden: true,
                    },
                    {
                        type: 'perf',
                        section: 'Melhorias de Desempenho',
                    },
                    {
                        type: 'revert',
                        hidden: true,
                    },
                    {
                        type: 'release',
                        hidden: true,
                    },
                ],
            },
        },
    },
};
