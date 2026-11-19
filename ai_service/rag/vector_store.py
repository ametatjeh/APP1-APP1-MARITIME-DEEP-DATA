import math
import hashlib
import re
from typing import List, Dict, Any

STOPWORDS = {
    'in', 'on', 'the', 'a', 'an', 'and', 'for', 'to', 'of', 'under',
    'between', 'is', 'at', 'by', 'as', 'with', 'from', 'or', 'meeting',
    'that', 'this', 'it', 'be', 'are', 'was', 'were'
}

def tokenize(text: str) -> List[str]:
    tokens = re.findall(r'\b\w+\b', text.lower())
    return [t for t in tokens if t not in STOPWORDS]

def generate_embedding(text: str, dimension: int = 128) -> List[float]:
    """
    Generates an enhanced normalized dense vector embedding for maritime intelligence.
    Employs stopword filtering, unigram + sub-word character n-grams,
    and bigram phrase feature hashing, followed by L2 unit normalization.
    """
    vector = [0.0] * dimension
    tokens = tokenize(text)
    if not tokens:
        return vector

    # Unigram features + character n-grams for sub-word morphology
    for token in tokens:
        h = int(hashlib.sha256(token.encode('utf-8')).hexdigest(), 16)
        idx = h % dimension
        weight = 1.0 + (len(token) * 0.15)
        vector[idx] += weight

        # Sub-word character trigrams (helps match longliner/longline, trawl/trawler)
        for i in range(len(token) - 2):
            sub = token[i:i+3]
            hs = int(hashlib.sha256(sub.encode('utf-8')).hexdigest(), 16)
            vector[hs % dimension] += 0.35

    # Bigram features for contextual phrase associations (e.g. "forced_labour", "flag_convenience")
    for i in range(len(tokens) - 1):
        bigram = f"{tokens[i]}_{tokens[i+1]}"
        h = int(hashlib.sha256(bigram.encode('utf-8')).hexdigest(), 16)
        idx = h % dimension
        vector[idx] += 2.2

    # L2 Normalization
    norm = math.sqrt(sum(x * x for x in vector))
    if norm > 0:
        vector = [round(x / norm, 6) for x in vector]

    return vector

def cosine_similarity(vec_a: List[float], vec_b: List[float]) -> float:
    if len(vec_a) != len(vec_b) or not vec_a:
        return 0.0
    dot = sum(a * b for a, b in zip(vec_a, vec_b))
    norm_a = math.sqrt(sum(a * a for a in vec_a))
    norm_b = math.sqrt(sum(b * b for b in vec_b))
    if norm_a == 0.0 or norm_b == 0.0:
        return 0.0
    return round(dot / (norm_a * norm_b), 4)

def chunk_text(text: str, max_chars: int = 600, overlap: int = 80) -> List[str]:
    chunks = []
    start = 0
    while start < len(text):
        end = min(start + max_chars, len(text))
        chunks.append(text[start:end])
        if end == len(text):
            break
        start += (max_chars - overlap)
    return chunks
