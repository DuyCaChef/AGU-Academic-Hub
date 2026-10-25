// Danh sách từ cấm học thuật và khiếm nhã phổ biến (đồng bộ hoàn toàn với từ điển Backend).
export const PROHIBITED_WORDS = [
    // Viết tắt & tiếng lóng phổ biến
    'đm', 'dm', 'đkm', 'dkm', 'đcm', 'dcm', 'vcl', 'vkl', 'clgt', 'cc', 'cl',
    // Từ ngữ thô tục nhạy cảm
    'lồn', 'cặc', 'buồi', 'địt', 'đụ', 'đĩ', 'dâm',
    // Cụm từ chửi bới / khiếm nhã
    'lồn má', 'vãi cặc', 'má nó', 'mẹ kiếp', 'chó chết', 'thằng chó', 'con mẹ',
    'mẹ mày', 'bố mày', 'cút', 'đậu má', 'thằng ngu', 'ngu vcl',
    // Xúc phạm, gian lận học thuật
    'chó', 'súc vật', 'lừa đảo', 'khốn nạn'
];

function escapeRegExp(s) {
    return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Xây dựng RegExp kèm ranh giới từ Unicode (Unicode Word Boundaries).
 * Tránh lỗi Scunthorpe (ví dụ: chữ "admin" chứa chuỗi con "dm" nhưng không phải từ cấm).
 */
export function buildWordRegex(word) {
    if (word.includes(' ')) {
        const parts = word.split(/\s+/).map((p) => {
            const chars = Array.from(p).map(escapeRegExp);
            return chars.join('[\\s._\\-*~]*');
        });
        const inner = parts.join('\\s+');
        return new RegExp('(?<=^|[^\\p{L}\\p{N}])' + inner + '(?=$|[^\\p{L}\\p{N}])', 'iu');
    }

    const chars = Array.from(word).map(escapeRegExp);
    const inner = chars.join('[\\s._\\-*~]*');
    return new RegExp('(?<=^|[^\\p{L}\\p{N}])' + inner + '(?=$|[^\\p{L}\\p{N}])', 'iu');
}

/**
 * Kiểm tra xem chuỗi văn bản có chứa từ cấm không và trả về mảng các từ vi phạm.
 */
export function detectProfanity(text) {
    if (!text || typeof text !== 'string') return [];

    // Chuẩn hóa văn bản về chữ thường
    const normalized = text.toLowerCase();
    const foundWords = [];

    // Ưu tiên cụm từ dài trước (VD: "vãi cặc" trước "cặc")
    const sortedWords = [...PROHIBITED_WORDS].sort((a, b) => b.length - a.length);

    sortedWords.forEach((word) => {
        const regex = buildWordRegex(word);
        if (regex.test(normalized)) {
            if (!foundWords.includes(word)) {
                foundWords.push(word);
            }
        }
    });

    return foundWords;
}

/**
 * Tạo danh sách các đoạn text kèm trạng thái vi phạm để render highlight trực tiếp.
 * Tôn trọng ranh giới từ để không highlight nhầm các từ hợp lệ như "admin".
 */
export function parseHighlightedText(text, violations) {
    if (!text) return [];
    if (!violations || violations.length === 0) {
        return [{ text, isBad: false }];
    }

    // Xây dựng regex bắt chính xác các từ vi phạm kèm ranh giới từ
    const sorted = [...violations].sort((a, b) => b.length - a.length);
    const innerPatterns = sorted.map((w) => {
        if (w.includes(' ')) {
            const parts = w.split(/\s+/).map((p) => Array.from(p).map(escapeRegExp).join('[\\s._\\-*~]*'));
            return parts.join('\\s+');
        }
        return Array.from(w).map(escapeRegExp).join('[\\s._\\-*~]*');
    });

    const combinedPattern = '((?<=^|[^\\p{L}\\p{N}])(?:' + innerPatterns.join('|') + ')(?=$|[^\\p{L}\\p{N}]))';
    const regex = new RegExp(combinedPattern, 'giu');

    const parts = text.split(regex);
    return parts
        .filter((part) => part !== '')
        .map((part) => ({
            text: part,
            isBad: sorted.some((w) => buildWordRegex(w).test(part)),
        }));
}

// Alias hỗ trợ tương thích ngược
export const parseHightLightedText = parseHighlightedText;
