import { test } from 'node:test';
import assert from 'node:assert/strict';
import { randomBytes } from 'node:crypto';
import { assertDrillDatabase, encryptBackup, decryptBackup } from '../security-backup-drill.mjs';

test('drill refuses real/arbitrary schemas and authenticated archive rejects tampering or a wrong key', () => {
  for (const name of ['municipal_hazards', 'mysql', '../municipal_hazards_backup_drill', 'municipal_hazards_restore_drill; DROP DATABASE mysql']) assert.throws(() => assertDrillDatabase(name));
  const key = randomBytes(32); const contents = Buffer.from('Synthetic fixture only');
  const archive = encryptBackup(contents, key);
  assert.deepEqual(decryptBackup(archive, key), contents);
  assert.throws(() => decryptBackup(archive, randomBytes(32)));
  for (const offset of [4, 16, archive.length - 1]) {
    const changed = Buffer.from(archive); changed[offset] ^= 1;
    assert.throws(() => decryptBackup(changed, key));
  }
});
