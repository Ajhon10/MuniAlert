"""Original instrumental bed; no sampled or third-party recordings."""
import array
import json
import math
from pathlib import Path
import wave

root = Path(__file__).resolve().parent.parent
directory = root / 'public' / 'audio'
directory.mkdir(parents=True, exist_ok=True)
rate = 44100
duration = 60
bpm = 88
beat = 60 / bpm
chords = [(220.0, 261.6256, 329.6276), (174.6141, 220.0, 261.6256), (261.6256, 329.6276, 391.9954), (195.9977, 246.9417, 293.6648)]
samples = array.array('h')
for i in range(rate * duration):
    time = i / rate
    chord = chords[int(time / (beat * 4)) % len(chords)]
    envelope = min(1, time / 1.8, (duration - time) / 2.4)
    phase = time % (beat / 2)
    pluck_note = chord[int(time / (beat / 2)) % 3] * 2
    pad = sum(math.sin(2 * math.pi * frequency * time) for frequency in chord) * 0.026
    pluck = math.sin(2 * math.pi * pluck_note * time) * math.exp(-phase * 13) * 0.07
    pulse = math.sin(2 * math.pi * 55 * time) * math.exp(-(time % beat) * 16) * 0.035
    left = math.tanh((pad + pluck + pulse) * envelope)
    right = math.tanh((pad + pluck * 0.8 + pulse + math.sin(2 * math.pi * chord[1] * 1.002 * time) * 0.012) * envelope)
    samples.extend((round(left * 32767), round(right * 32767)))
with wave.open(str(directory / 'original-music.wav'), 'wb') as output:
    output.setnchannels(2)
    output.setsampwidth(2)
    output.setframerate(rate)
    output.writeframes(samples.tobytes())
(directory / 'provenance.json').write_text(json.dumps({
    'music': 'Original synthesized instrumental; no external recordings or samples.',
    'narration': 'Locally generated using Microsoft Zira Desktop from the authored narration script.',
    'duration_seconds': duration,
    'sample_rate': rate,
    'bpm': bpm,
}, indent=2) + '\n', encoding='utf-8')
print('Generated a 60-second original stereo instrumental bed.')
