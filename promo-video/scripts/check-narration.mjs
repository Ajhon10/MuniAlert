import {readFileSync} from 'node:fs';
import {resolve} from 'node:path';
import {createHash} from 'node:crypto';
const root = resolve(import.meta.dirname, '..');
const read = file => JSON.parse(readFileSync(resolve(root, file), 'utf8'));
const digest = bytes => createHash('sha256').update(bytes).digest('hex');
const script = read('scripts/narration.json');
const manifest = read('public/audio/narration-manifest.json');
if (manifest.voice !== 'af_heart' || manifest.engine !== 'kokoro-js@1.2.1' || manifest.model !== 'onnx-community/Kokoro-82M-v1.0-ONNX' || manifest.clips.length !== 7 || script.length !== 7) throw Error('The requested KIKOS narration preset and seven clips are required.');
if (digest(readFileSync(resolve(root, 'node_modules/kokoro-js/voices/af_heart.bin'))) !== manifest.voiceSha256) throw Error('Narration voice preset hash differs.');
for (const [index, line] of script.entries()) {
  const clip = manifest.clips[index];
  if (clip.id !== line.id || clip.text !== line.text || clip.file !== `${line.id}.wav` || clip.voice !== manifest.voice) throw Error(`Narration source differs: ${line.id}`);
  const wav = readFileSync(resolve(root, 'public/audio', clip.file));
  if (digest(wav) !== clip.sha256 || wav.toString('ascii', 0, 4) !== 'RIFF' || wav.toString('ascii', 8, 12) !== 'WAVE') throw Error(`Narration bytes differ: ${line.id}`);
  let offset = 12; let byteRate = 0; let size = 0;
  while (offset + 8 <= wav.length) {
    const name = wav.toString('ascii', offset, offset + 4); const length = wav.readUInt32LE(offset + 4);
    if (name === 'fmt ') byteRate = wav.readUInt32LE(offset + 16);
    if (name === 'data') { size = length; if (offset + 8 + length > wav.length) throw Error(`Truncated narration: ${line.id}`); }
    offset += 8 + length + length % 2;
  }
  const seconds = size / byteRate;
  const window = line.sceneSeconds - .6 - (index === script.length - 1 ? .1 : .5);
  if (!Number.isFinite(seconds) || seconds < 1 || Math.abs(seconds - clip.seconds) > .01 || seconds > window || clip.speed < 1 || clip.speed > 1.16) throw Error(`Narration timing is invalid: ${line.id}`);
}
console.log('Verified all seven af_heart narration clips, preset/clip hashes and scene timing.');
