import { mkdir, readFile, writeFile, copyFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { createHash } from 'node:crypto';
import { KokoroTTS } from 'kokoro-js';
import { env } from '@huggingface/transformers';

const root = resolve(import.meta.dirname, '..');
const workspace = resolve(root, '..');
const model = 'onnx-community/Kokoro-82M-v1.0-ONNX';
const voice = 'af_heart';
const narrationStart = 0.6;
const lines = JSON.parse(await readFile(resolve(root, 'scripts/narration.json'), 'utf8'));
const staging = resolve(workspace, '.cache/kokoro-narration');
const output = resolve(root, 'public/audio');
env.cacheDir = resolve(workspace, '.cache/kokoro-tts');
if (process.argv.includes('--offline')) env.allowRemoteModels = false;
await mkdir(staging, {recursive:true});
await mkdir(output, {recursive:true});
console.log('Generating narration locally with Kokoro af_heart, matching KIKOS Order Planner.');
async function generateNarration() {
const tts = await KokoroTTS.from_pretrained(model, {dtype:'q8', device:'cpu'});
const clips = [];
for (const [index, line] of lines.entries()) {
  const window = line.sceneSeconds - narrationStart - (index === lines.length - 1 ? 0.1 : 0.5);
  let speed = 1.02;
  let audio = await tts.generate(line.text, {voice, speed});
  let duration = audio.audio.length / audio.sampling_rate;
  if (duration > window) {
    speed = Math.min(1.16, speed * duration / window + 0.01);
    audio = await tts.generate(line.text, {voice, speed});
    duration = audio.audio.length / audio.sampling_rate;
  }
  if (duration > window) throw Error(`Shorten narration ${line.id}; natural-speed speech exceeds its scene window.`);
  let peak = 0;
  for (const sample of audio.audio) peak = Math.max(peak, Math.abs(sample));
  if (!Number.isFinite(peak) || peak === 0) throw Error(`Invalid narration audio: ${line.id}`);
  const gain = Math.min(2.5, 0.75 / peak);
  for (let index = 0; index < audio.audio.length; index++) audio.audio[index] *= gain;
  const file = `${line.id}.wav`;
  await audio.save(resolve(staging, file));
  const sha256 = createHash('sha256').update(await readFile(resolve(staging, file))).digest('hex');
  clips.push({...line, file, voice, model, dtype:'q8', speed, seconds:duration, scene_seconds:line.sceneSeconds, starts_at_scene_second:narrationStart, gain, sha256});
  console.log(`${line.id}: ${duration.toFixed(2)}s / ${window.toFixed(2)}s window, speed ${speed.toFixed(3)}`);
}
// Replace every old Windows-voice clip only after all seven new clips are valid.
for (const clip of clips) await copyFile(resolve(staging, clip.file), resolve(output, clip.file));
const modelPath = resolve(env.cacheDir, model, 'onnx/model_quantized.onnx');
const modelSha256 = createHash('sha256').update(await readFile(modelPath)).digest('hex');
const voiceSha256 = createHash('sha256').update(await readFile(resolve(root, 'node_modules/kokoro-js/voices/af_heart.bin'))).digest('hex');
await writeFile(resolve(output, 'narration-manifest.json'), JSON.stringify({generatedAt:new Date().toISOString(),engine:'kokoro-js@1.2.1',voice,voiceSha256,model,modelSha256,dtype:'q8',device:'cpu',reference:'Same generic preset and model as KIKOS Order Planner; new MuniAlert speech, no personal voice cloning.',clips}, null, 2)+'\n');
await writeFile(resolve(output, 'narration-script.json'), JSON.stringify(Object.fromEntries(lines.map(line=>[line.id,line.text])),null,2)+'\n');
await mkdir(resolve(root, 'out'), {recursive:true});
await writeFile(resolve(root, 'out/audio-timing.json'), JSON.stringify(clips.map(({file,seconds,scene_seconds,starts_at_scene_second,voice,speed})=>({file,seconds,scene_seconds,starts_at_scene_second,voice,speed})),null,2)+'\n');
console.log('Replaced all seven narration clips with the requested af_heart voice.');
}
generateNarration().catch(error => { console.error(error.message); process.exitCode = 1; });
