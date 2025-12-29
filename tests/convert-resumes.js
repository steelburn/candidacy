/**
 * Test Resume Conversion Script
 * 
 * Converts markdown resumes to PDF and DOCX formats for automated testing.
 * - Software Developer resume -> PDF
 * - Project Manager resume -> DOCX
 */

const fs = require('fs');
const path = require('path');
const { mdToPdf } = require('md-to-pdf');
const { Document, Packer, Paragraph, TextRun, HeadingLevel, BorderStyle } = require('docx');
const { marked } = require('marked');

const FIXTURES_DIR = path.join(__dirname, 'fixtures');

/**
 * Convert markdown to PDF using md-to-pdf
 */
async function convertToPdf(inputFile, outputFile) {
    console.log(`📄 Converting ${inputFile} to PDF...`);

    const inputPath = path.join(FIXTURES_DIR, inputFile);
    const outputPath = path.join(FIXTURES_DIR, outputFile);

    try {
        const pdf = await mdToPdf(
            { path: inputPath },
            {
                dest: outputPath,
                pdf_options: {
                    format: 'A4',
                    margin: {
                        top: '20mm',
                        right: '20mm',
                        bottom: '20mm',
                        left: '20mm'
                    }
                },
                css: `
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; }
                    h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
                    h2 { color: #34495e; margin-top: 20px; }
                    h3 { color: #7f8c8d; }
                    strong { color: #2c3e50; }
                    hr { border: none; border-top: 1px solid #bdc3c7; margin: 20px 0; }
                    ul { padding-left: 20px; }
                    li { margin-bottom: 5px; }
                `,
                launch_options: {
                    executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || undefined,
                    args: ['--no-sandbox', '--disable-setuid-sandbox']
                }
            }
        );

        console.log(`   ✅ Created: ${outputPath}`);
        return true;
    } catch (error) {
        console.error(`   ❌ Error converting to PDF: ${error.message}`);
        return false;
    }
}

/**
 * Parse markdown and convert to DOCX using docx library
 */
async function convertToDocx(inputFile, outputFile) {
    console.log(`📄 Converting ${inputFile} to DOCX...`);

    const inputPath = path.join(FIXTURES_DIR, inputFile);
    const outputPath = path.join(FIXTURES_DIR, outputFile);

    try {
        const markdown = fs.readFileSync(inputPath, 'utf-8');
        const lines = markdown.split('\n');
        const children = [];

        let currentList = [];
        let inList = false;

        for (const line of lines) {
            // Flush list if not a list item
            if (inList && !line.startsWith('- ') && !line.startsWith('* ')) {
                currentList.forEach(item => {
                    children.push(new Paragraph({
                        text: item,
                        bullet: { level: 0 }
                    }));
                });
                currentList = [];
                inList = false;
            }

            // H1
            if (line.startsWith('# ')) {
                children.push(new Paragraph({
                    text: line.substring(2),
                    heading: HeadingLevel.HEADING_1,
                    spacing: { after: 200 }
                }));
            }
            // H2
            else if (line.startsWith('## ')) {
                children.push(new Paragraph({
                    text: line.substring(3),
                    heading: HeadingLevel.HEADING_2,
                    spacing: { before: 300, after: 100 }
                }));
            }
            // H3
            else if (line.startsWith('### ')) {
                children.push(new Paragraph({
                    text: line.substring(4),
                    heading: HeadingLevel.HEADING_3,
                    spacing: { before: 200, after: 100 }
                }));
            }
            // Horizontal rule
            else if (line.startsWith('---')) {
                children.push(new Paragraph({
                    text: '',
                    border: {
                        bottom: { color: 'auto', space: 1, style: BorderStyle.SINGLE, size: 6 }
                    },
                    spacing: { before: 200, after: 200 }
                }));
            }
            // List items
            else if (line.startsWith('- ') || line.startsWith('* ')) {
                inList = true;
                currentList.push(line.substring(2));
            }
            // Bold text lines (contact info)
            else if (line.startsWith('**') && line.includes(':**')) {
                const cleanLine = line.replace(/\*\*/g, '').replace(/\s\s$/g, '');
                const [label, ...valueParts] = cleanLine.split(':');
                const value = valueParts.join(':').trim();

                children.push(new Paragraph({
                    children: [
                        new TextRun({ text: label + ': ', bold: true }),
                        new TextRun({ text: value })
                    ],
                    spacing: { after: 50 }
                }));
            }
            // Italic text (dates)
            else if (line.startsWith('*') && line.endsWith('*') && !line.startsWith('**')) {
                children.push(new Paragraph({
                    children: [
                        new TextRun({ text: line.replace(/\*/g, ''), italics: true })
                    ],
                    spacing: { after: 100 }
                }));
            }
            // Regular paragraph
            else if (line.trim() && !line.startsWith('**')) {
                // Handle inline formatting
                const text = line
                    .replace(/\*\*([^*]+)\*\*/g, '$1')  // Remove bold markers
                    .replace(/\*([^*]+)\*/g, '$1');     // Remove italic markers

                children.push(new Paragraph({
                    text: text,
                    spacing: { after: 100 }
                }));
            }
        }

        // Flush remaining list items
        if (currentList.length > 0) {
            currentList.forEach(item => {
                children.push(new Paragraph({
                    text: item,
                    bullet: { level: 0 }
                }));
            });
        }

        const doc = new Document({
            sections: [{
                properties: {},
                children: children
            }]
        });

        const buffer = await Packer.toBuffer(doc);
        fs.writeFileSync(outputPath, buffer);

        console.log(`   ✅ Created: ${outputPath}`);
        return true;
    } catch (error) {
        console.error(`   ❌ Error converting to DOCX: ${error.message}`);
        return false;
    }
}

/**
 * Main conversion function
 */
async function main() {
    console.log('🔄 Starting test resume conversion...\n');

    // Ensure fixtures directory exists
    if (!fs.existsSync(FIXTURES_DIR)) {
        fs.mkdirSync(FIXTURES_DIR, { recursive: true });
    }

    const results = {
        pdf: false,
        docx: false
    };

    // Convert Software Developer resume to PDF
    results.pdf = await convertToPdf(
        'test_resume_software_developer.md',
        'test_resume_software_developer.pdf'
    );

    // Convert Project Manager resume to DOCX
    results.docx = await convertToDocx(
        'test_resume_project_manager.md',
        'test_resume_project_manager.docx'
    );

    console.log('\n📊 Conversion Summary:');
    console.log(`   PDF: ${results.pdf ? '✅ Success' : '❌ Failed'}`);
    console.log(`   DOCX: ${results.docx ? '✅ Success' : '❌ Failed'}`);

    if (results.pdf && results.docx) {
        console.log('\n🎉 All conversions completed successfully!');
        process.exit(0);
    } else {
        console.log('\n⚠️  Some conversions failed.');
        process.exit(1);
    }
}

main().catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});
