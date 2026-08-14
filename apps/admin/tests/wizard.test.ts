import { describe, expect, it } from 'vitest';

import {
  INITIAL_PROGRESS,
  WIZARD_STEPS,
  checklist,
  completeStep,
  finish,
  nextStep,
  previousStep,
  restart,
  skipStep,
} from '../src/wizard/wizardFlow';
import {
  WIZARD_ROLES,
  assessPassword,
  canSubmit,
  generatePassword,
  validateNewUser,
  type NewUserDraft,
} from '../src/wizard/newUser';

const draft: NewUserDraft = {
  username: 'nadia.rahman',
  email: 'nadia@example.test',
  password: 'correct horse battery',
  role: 'edulume_counsellor',
};

const free = { usernameTaken: false, emailTaken: false };

describe('the wizard flow', () => {
  it('runs the eight steps in order', () => {
    expect(WIZARD_STEPS.map((step) => step.id)).toEqual([
      'welcome',
      'identity',
      'contact',
      'style',
      'content',
      'user',
      'essentials',
      'done',
    ]);
  });

  it('never makes a step required, so the wizard can always be left', () => {
    expect(WIZARD_STEPS.every((step) => !step.isRequired)).toBe(true);
  });

  it('walks forwards and backwards and stops at both ends', () => {
    expect(nextStep('welcome')).toBe('identity');
    expect(nextStep('done')).toBe('done');
    expect(previousStep('identity')).toBe('welcome');
    expect(previousStep('welcome')).toBe('welcome');
  });

  it('records a completed step and advances', () => {
    const progress = completeStep(INITIAL_PROGRESS, 'welcome');

    expect(progress.completed).toEqual(['welcome']);
    expect(progress.currentStep).toBe('identity');
  });

  it('does not record a step twice', () => {
    const once = completeStep(INITIAL_PROGRESS, 'identity');

    expect(completeStep(once, 'identity').completed).toEqual(['identity']);
  });

  it('advances on skip without marking the step done', () => {
    const progress = skipStep(INITIAL_PROGRESS, 'contact');

    expect(progress.currentStep).toBe('style');
    expect(progress.completed).toEqual([]);
  });

  it('finishes, and re-running clears the finished flag while keeping what was done', () => {
    const finished = finish(completeStep(INITIAL_PROGRESS, 'style'));

    expect(finished.isFinished).toBe(true);

    const again = restart(finished);

    expect(again.isFinished).toBe(false);
    expect(again.currentStep).toBe('welcome');
    expect(again.completed).toEqual(['style']);
  });

  it('marks itself finished once the last step is completed', () => {
    expect(completeStep(INITIAL_PROGRESS, 'done').isFinished).toBe(true);
  });

  it('closes with a checklist that shows the skipped steps as gaps', () => {
    const progress = completeStep(skipStep(INITIAL_PROGRESS, 'welcome'), 'identity');
    const items = checklist(progress);

    expect(items.map((item) => item.step)).toEqual([
      'identity',
      'contact',
      'style',
      'content',
      'user',
      'essentials',
    ]);
    expect(items.find((item) => item.step === 'identity')?.isComplete).toBe(true);
    expect(items.find((item) => item.step === 'contact')?.isComplete).toBe(false);
  });
});

describe('the password field', () => {
  it('calls a short password weak and says what is missing', () => {
    const assessment = assessPassword('short');

    expect(assessment.strength).toBe('weak');
    expect(assessment.advice).toContain('Use at least 12 characters.');
    expect(assessment.advice).toContain('Add a number.');
  });

  it('climbs as characters classes are added', () => {
    expect(assessPassword('alllowercase1').strength).toBe('fair');
    expect(assessPassword('Alllowercase1').strength).toBe('strong');
    expect(assessPassword('Alllowercase1!').strength).toBe('excellent');
  });

  it('caps the meter at four so a very long password does not overflow it', () => {
    expect(assessPassword('AnExtremelyLongPassphrase1!').score).toBe(4);
  });

  it('generates from the injected random source, deterministically for a fixed source', () => {
    const bytes = (length: number): Uint8Array =>
      Uint8Array.from({ length }, (_value, index) => index);

    expect(generatePassword(bytes, 8)).toBe(generatePassword(bytes, 8));
    expect(generatePassword(bytes, 20)).toHaveLength(20);
  });

  it('generates something the strength meter is happy with', () => {
    const bytes = (length: number): Uint8Array =>
      Uint8Array.from({ length }, (_value, index) => index * 7 + 3);

    expect(assessPassword(generatePassword(bytes, 20)).strength).toBe('excellent');
  });
});

describe('validating the additional user', () => {
  it('accepts a well-formed draft on a free username and email', () => {
    expect(validateNewUser(draft, free)).toEqual([]);
    expect(canSubmit(draft, free)).toBe(true);
  });

  it('reports a taken username inline rather than on submit', () => {
    const errors = validateNewUser(draft, { usernameTaken: true, emailTaken: false });

    expect(errors).toEqual([{ field: 'username', message: 'That username is already in use.' }]);
  });

  it('reports a taken email inline', () => {
    expect(validateNewUser(draft, { usernameTaken: false, emailTaken: true })[0]?.field).toBe(
      'email'
    );
  });

  it('rejects an empty username, a malformed one, a bad address and a short password', () => {
    expect(validateNewUser({ ...draft, username: '  ' }, free)[0]?.message).toBe(
      'Choose a username.'
    );
    expect(validateNewUser({ ...draft, username: '-nope-' }, free)[0]?.field).toBe('username');
    expect(validateNewUser({ ...draft, email: 'not-an-address' }, free)[0]?.field).toBe('email');
    expect(validateNewUser({ ...draft, password: 'tiny' }, free)[0]?.field).toBe('password');
  });

  it('rejects a role the product does not define, including administrator', () => {
    const errors = validateNewUser({ ...draft, role: 'administrator' as never }, free);

    expect(errors).toEqual([{ field: 'role', message: 'Choose a role.' }]);
  });

  it('offers exactly the three product roles', () => {
    expect(WIZARD_ROLES.map((choice) => choice.role)).toEqual([
      'edulume_site_manager',
      'edulume_content_editor',
      'edulume_counsellor',
    ]);
  });
});
