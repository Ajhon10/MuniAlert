import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { readFileSync, statSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const probe = process.platform === 'win32' ? resolve(root, 'node_modules/@remotion/compositor-win32-x64-msvc/ffprobe.exe') : 'ffprobe';
const ffmpeg = process.platform === 'win32' ? resolve(root, 'node_modules/@remotion/compositor-win32-x64-msvc/ffmpeg.exe') : 'ffmpeg';
const results = [];
const captureManifest = JSON.parse(readFileSync(resolve(root, 'public/screens/manifest.json'), 'utf8'));
if (captureManifest.errors.length || !captureManifest.files.some(file => file.name === 'map.png')) throw Error('Current Basista map capture is required.');
for (const file of captureManifest.files) if (createHash('sha256').update(readFileSync(resolve(root, 'public/screens', file.name))).digest('hex') !== file.sha256) throw Error(`Promotional capture hash differs: ${file.name}`);
for (const [name, width, height] of [['MuniAlert-portrait.mp4', 1080, 1920], ['MuniAlert-landscape.mp4', 1920, 1080]]) {
  const file = resolve(root, 'out', name);
  const metadata = JSON.parse(execFileSync(probe, ['-v', 'error', '-print_format', 'json', '-show_format', '-show_streams', file], { encoding: 'utf8', windowsHide: true }));
  const video = metadata.streams.find(stream => stream.codec_type === 'video');
  const audio = metadata.streams.find(stream => stream.codec_type === 'audio');
  if (!video || !audio || video.width !== width || video.height !== height || video.codec_name !== 'h264' || audio.codec_name !== 'aac' || video.r_frame_rate !== '30/1' || Number(video.nb_frames) !== 1800 || Math.abs(Number(metadata.format.duration) - 60) > 0.1) throw Error(`Invalid exported video metadata: ${name}`);
  // The matched Remotion FFmpeg omits volumedetect. Decode PCM and measure it
  // directly, checking the actual exported mix rather than just source clips.
  const wav = execFileSync(ffmpeg, ['-v', 'error', '-nostdin', '-i', file, '-vn', '-ac', '2', '-ar', '48000', '-c:a', 'pcm_s16le', '-f', 'wav', 'pipe:1'], { windowsHide: true, maxBuffer: 32 * 1024 * 1024 });
  if (wav.toString('ascii', 0, 4) !== 'RIFF' || wav.toString('ascii', 8, 12) !== 'WAVE') throw Error(`Audio decode failed: ${name}`);
  let start = 12;
  while (start + 8 < wav.length && wav.toString('ascii', start, start + 4) !== 'data') {
    const size = wav.readUInt32LE(start + 4);
    start += 8 + size + (size % 2);
  }
  if (start + 8 >= wav.length) throw Error(`No decoded audio: ${name}`);
  const dataSize = wav.readUInt32LE(start + 4);
  start += 8;
  const end = Math.min(wav.length, start + dataSize);
  let maximum = 0;
  let squares = 0;
  let samples = 0;
  for (let offset = start; offset + 1 < end; offset += 2) {
    const value = wav.readInt16LE(offset) / 32768;
    maximum = Math.max(maximum, Math.abs(value));
    squares += value * value;
    samples++;
  }
  const peak = Number((20 * Math.log10(maximum)).toFixed(2));
  const mean = Number((20 * Math.log10(Math.sqrt(squares / samples))).toFixed(2));
  if (!Number.isFinite(peak) || !Number.isFinite(mean) || peak >= 0 || mean < -60 || samples < 48000 * 2 * 59) throw Error(`Silent, clipped or incomplete audio detected: ${name}`);
  results.push({ name, width, height, fps: video.r_frame_rate, frames: video.nb_frames, seconds: Number(metadata.format.duration), video_codec: video.codec_name, pixel_format: video.pix_fmt, audio_codec: audio.codec_name, audio_channels: audio.channels, audio_peak_db: peak, audio_mean_db: mean, bytes: statSync(file).size, sha256: createHash('sha256').update(readFileSync(file)).digest('hex') });
}
writeFileSync(resolve(root, 'out/verification.json'), JSON.stringify({ passed: true, checkedAt: new Date().toISOString(), outputs: results, captures: captureManifest, narrationTiming: JSON.parse(readFileSync(resolve(root, 'out/audio-timing.json'), 'utf8')), notes: ['Review samples cover all seven scenes and the Basista map transition in portrait and landscape.', 'Screens use synthetic fixtures and block API writes.', 'No signed Android, deployment or cloud-service acceptance implied.'] }, null, 2));
console.log(JSON.stringify(results, null, 2));
