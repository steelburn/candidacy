/**
 * useMatchAnalysis Composable
 * 
 * Shared logic for parsing and displaying AI match analysis.
 * Used by CandidateDetail.vue and MatchList.vue
 */

/**
 * Parse structured analysis from AI-generated text.
 * Extracts strengths, gaps, and recommendations from formatted text.
 * 
 * @param {string} text - Raw analysis text from AI
 * @returns {Object|null} Parsed sections or null if unparseable
 */
export function parseAnalysis(text) {
    if (!text) return null

    const sections = {
        strengths: [],
        gaps: [],
        recommendation: ''
    }

    let foundAny = false

    // Extract Strengths (handle typos)
    const strengthsMatch = text.match(/(?:STRENGTHS?|STRENGHTHS?|STRENTHS?)\s*:([\s\S]*?)(?=(?:GAPS?|WEAKNESS(?:ES)?)\s*:|RECOMMENDATION\s*:|$)/i)
    if (strengthsMatch && strengthsMatch[1]) {
        foundAny = true
        sections.strengths = extractListItems(strengthsMatch[1])
    }

    // Extract Gaps
    const gapsMatch = text.match(/(?:GAPS?|WEAKNESS(?:ES)?)\s*:([\s\S]*?)(?=RECOMMENDATION\s*:|$)/i)
    if (gapsMatch && gapsMatch[1]) {
        foundAny = true
        sections.gaps = extractListItems(gapsMatch[1])
    }

    // Extract Recommendation
    const recMatch = text.match(/RECOMMENDATION\s*:([\s\S]*)/i)
    if (recMatch && recMatch[1]) {
        foundAny = true
        sections.recommendation = recMatch[1].trim()
    }

    return foundAny ? sections : null
}

/**
 * Extract list items from a text block.
 * Handles various list formats: -, •, *, 1., 1), a., a)
 * 
 * @param {string} block - Text block containing list items
 * @returns {string[]} Array of cleaned list items
 */
export function extractListItems(block) {
    if (!block) return []
    return block
        .split('\n')
        .map(line => line.trim())
        .filter(line => line.length > 2)
        .map(line => {
            // Remove common list prefixes
            return line
                .replace(/^[-•*]\s*/, '')           // - or • or *
                .replace(/^\d+[.)]\s*/, '')          // 1. or 1)
                .replace(/^[a-z][.)]\s*/i, '')       // a. or a)
                .trim()
        })
        .filter(line => line.length > 2 && !line.match(/^(GAPS?|STRENGTHS?|RECOMMENDATION|SCORE)/i))
}

/**
 * Get CSS class for score badge based on threshold.
 * 
 * @param {number|string} score - Match score percentage
 * @returns {string} CSS class name
 */
export function getScoreClass(score) {
    const numScore = Number(score)
    if (numScore >= 80) return 'score-high'
    if (numScore >= 60) return 'score-medium'
    return 'score-low'
}

/**
 * Format date for display.
 * 
 * @param {string|Date} date - Date to format
 * @returns {string} Formatted date string
 */
export function formatDate(date) {
    if (!date) return ''
    return new Date(date).toLocaleDateString()
}

/**
 * Truncate analysis text with ellipsis.
 * 
 * @param {string} text - Text to truncate
 * @param {number} maxLength - Maximum length (default 150)
 * @returns {string} Truncated text
 */
export function truncateAnalysis(text, maxLength = 150) {
    if (!text) return ''
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text
}

/**
 * Parse skills from various formats.
 * Handles arrays, JSON strings, and comma-separated strings.
 * 
 * @param {any} skills - Skills data in various formats
 * @returns {string[]} Array of skill strings
 */
export function parseSkills(skills) {
    if (!skills) return []
    if (Array.isArray(skills)) return skills
    try {
        const parsed = JSON.parse(skills)
        return Array.isArray(parsed) ? parsed : String(parsed).split(',').map(s => s.trim())
    } catch {
        return String(skills).split(',').map(s => s.trim())
    }
}

/**
 * Parse JSON array field (experience, education).
 * 
 * @param {any} data - Data to parse
 * @returns {Array} Parsed array or empty array
 */
export function parseJsonArray(data) {
    if (!data) return []
    if (Array.isArray(data)) return data
    try {
        return JSON.parse(data)
    } catch {
        return []
    }
}

/**
 * Get skills as array from various vacancy formats.
 * 
 * @param {any} skills - Skills data (string, JSON string, or array)
 * @returns {string[]} Array of skill strings
 */
export function getSkillsArray(skills) {
    if (!skills) return []
    if (Array.isArray(skills)) return skills
    if (typeof skills === 'string') {
        try {
            const parsed = JSON.parse(skills)
            return Array.isArray(parsed) ? parsed : [skills]
        } catch {
            return skills.split(',').map(s => s.trim()).filter(s => s)
        }
    }
    return []
}
