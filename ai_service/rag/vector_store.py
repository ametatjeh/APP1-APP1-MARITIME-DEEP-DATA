import math
import hashlib
import re
from typing import List, Dict, Any

def tokenize(text: str) -> List[str]:
    return re.findall(r'\b\w+\b', text.lower())

def generate_embedding(text: str, dimension: int = 64) -> List[float]:
    """
    Generates a normalized dense vector embedding for semantic search.
    Employs deterministic feature hashing across unigrams and bigrams,
    followed by L2 normalization, ensuring consistent reproducible vectors.
    """
    vector = [0.0] * dimension
    tokens = tokenize(text)
    if not tokens:
        return vector

    # Unigram features
    for token in tokens:
        h = int(hashlib.sha256(token.encode('utf-8')).hexdigest(), 16)
        idx = h % dimension
        sign = 1.0 if (h >> 8) % 2 == 0 else -1.0
        weight = 1.0 + (len(token) * 0.1)
        vector[idx] += sign * weight

    # Bigram features for contextual phrase associations
    for i in range(len(tokens) - 1):
        bigram = f"{tokens[i]}_{tokens[i+1]}"
        h = int(hashlib.sha256(bigram.encode('utf-8')).hexdigest(), 16)
        idx = h % dimension
        sign = 1.0 if (h >> 8) % 2 == 0 else -1.0
        vector[idx] += sign * 1.5

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
