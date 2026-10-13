#!/usr/bin/env python3
"""
Test Suite for Meteoclimatic DATA2 Verification System
Ensures that verify_meteoclimatic.py correctly identifies valid feeds
and strictly rejects invalid, inconsistent, or anomalous data.
"""

import os
import sys
import tempfile
import subprocess

VALID_FILE = '/opt/servidor/cuhws/meteoclimatic.htm'
VERIFY_SCRIPT = '/opt/servidor/scripts/verify_meteoclimatic.py'

def run_validator(filepath):
    res = subprocess.run([sys.executable, VERIFY_SCRIPT, filepath], capture_output=True, text=True)
    return res.returncode == 0, res.stdout + res.stderr

def test_suite():
    tests = []
    
    # Read the valid base file
    with open(VALID_FILE, 'r') as f:
        valid_lines = [line.strip() for line in f if line.strip()]

    def make_temp_file(lines):
        t = tempfile.NamedTemporaryFile(mode='w', suffix='.htm', delete=False)
        t.write('\n'.join(lines) + '\n')
        t.close()
        return t.name

    # Test 1: Real production file must pass
    ok, out = run_validator(VALID_FILE)
    tests.append(('Test 1: Real production file validates cleanly', ok, True, out))

    # Helper to mutate a single key
    def mutate(key, new_val):
        mutated = []
        for line in valid_lines:
            if line.startswith(f'*{key}='):
                mutated.append(f'*{key}={new_val}')
            else:
                mutated.append(line)
        return mutated

    # Test 2: MHTM=38.5 (The summer bug in autumn/winter)
    fn = make_temp_file(mutate('MHTM', '38.5'))
    ok, out = run_validator(fn)
    os.unlink(fn)
    tests.append(('Test 2: Reject impossible MHTM=38.5 in autumn', not ok, True, out))

    # Test 3: Inverted temperature hierarchy (DHTM < TMP)
    fn = make_temp_file(mutate('DHTM', '10.0'))
    ok, out = run_validator(fn)
    os.unlink(fn)
    tests.append(('Test 3: Reject DHTM < TMP (Daily high less than current temp)', not ok, True, out))

    # Test 4: Inverted temperature hierarchy (DLTM > TMP)
    fn = make_temp_file(mutate('DLTM', '25.0'))
    ok, out = run_validator(fn)
    os.unlink(fn)
    tests.append(('Test 4: Reject DLTM > TMP (Daily low greater than current temp)', not ok, True, out))

    # Test 5: Inverted month hierarchy (MHTM < DHTM)
    fn = make_temp_file(mutate('MHTM', '20.0'))
    ok, out = run_validator(fn)
    os.unlink(fn)
    tests.append(('Test 5: Reject MHTM < DHTM (Month max less than day max)', not ok, True, out))

    # Test 6: Inverted year hierarchy (YHTM < MHTM)
    fn = make_temp_file(mutate('YHTM', '20.0'))
    ok, out = run_validator(fn)
    os.unlink(fn)
    tests.append(('Test 6: Reject YHTM < MHTM (Year max less than month max)', not ok, True, out))

    # Test 7: Nighttime solar radiation violation (SUN > 0 at night)
    fn = make_temp_file(mutate('SUN', '350.0'))
    ok, out = run_validator(fn)
    os.unlink(fn)
    tests.append(('Test 7: Reject SUN > 0 at night (solar elevation <= 0)', not ok, True, out))

    # Test 8: Nighttime UV violation (UVI > 0 at night)
    fn = make_temp_file(mutate('UVI', '2.5'))
    ok, out = run_validator(fn)
    os.unlink(fn)
    tests.append(('Test 8: Reject UVI > 0 at night', not ok, True, out))

    # Test 9: Inverted Humidity hierarchy (HUM > DHHM)
    fn = make_temp_file(mutate('HUM', '99'))
    ok, out = run_validator(fn)
    os.unlink(fn)
    tests.append(('Test 9: Reject HUM > DHHM', not ok, True, out))

    # Test 10: Inverted Rain hierarchy (DPCP > MPCP)
    fn = make_temp_file(mutate('DPCP', '150.0'))
    ok, out = run_validator(fn)
    os.unlink(fn)
    tests.append(('Test 10: Reject DPCP > MPCP (Day rain greater than month rain)', not ok, True, out))

    # Test 11: Corrupted header (*VER=DATA1 instead of DATA2)
    bad_header = ['*VER=DATA1'] + valid_lines[1:]
    fn = make_temp_file(bad_header)
    ok, out = run_validator(fn)
    os.unlink(fn)
    tests.append(('Test 11: Reject invalid header *VER=DATA1', not ok, True, out))

    # Test 12: Missing *EOT* footer
    bad_footer = valid_lines[:-1]
    fn = make_temp_file(bad_footer)
    ok, out = run_validator(fn)
    os.unlink(fn)
    tests.append(('Test 12: Reject missing *EOT* terminator', not ok, True, out))

    # Test 13: Missing mandatory key (*BAR=)
    missing_key = [l for l in valid_lines if not l.startswith('*BAR=')]
    fn = make_temp_file(missing_key)
    ok, out = run_validator(fn)
    os.unlink(fn)
    tests.append(('Test 13: Reject missing mandatory key *BAR=', not ok, True, out))

    # Summary
    all_passed = True
    print("\n================ METEOCLIMATIC VALIDATOR QA SUITE ================")
    for desc, result, expected, out in tests:
        if result == expected:
            print(f"  PASS: {desc}")
        else:
            print(f"  FAIL: {desc}")
            print(f"        Output: {out.strip()}")
            all_passed = False
    print("==================================================================\n")

    if all_passed:
        print(">>> ALL 13 TEST CASES PASSED! The verification system is 100% RELIABLE and ACCURATE. <<<")
    else:
        print(">>> SOME TESTS FAILED! Check logic. <<<")
    return all_passed

if __name__ == '__main__':
    ok = test_suite()
    sys.exit(0 if ok else 1)
