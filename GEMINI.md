# Project Guidelines & Meteoclimatic DATA2 Rule

## 1. Obligatory Meteoclimatic Verification
Whenever working on, modifying, or answering questions regarding:
- The weather website (`/opt/servidor/cuhws/`, HTML, JS, CSS, charts)
- Meteo update scripts (`/opt/servidor/scripts/`)
- Cumulus MX configuration or data

You MUST run the Meteoclimatic QA verification script before concluding:
```bash
python3 /opt/servidor/scripts/verify_meteoclimatic.py
```

## 2. Integrity Constraints
- `meteoclimatic.htm` must strictly follow the Meteoclimatic DATA2 specification.
- Monthly extremes (`MHTM`, `MLTM`, etc.) must reflect actual observed extremes from `month.ini` and `dayfile.txt`, never hardcoded summer values.
- Physical inequalities must hold at all times:
  - `YLTM <= MLTM <= DLTM <= TMP <= DHTM <= MHTM <= YHTM`
  - `YLHM <= MLHM <= DLHM <= HUM <= DHHM <= MHHM <= YHHM`
  - `YLBR <= MLBR <= DLBR <= BAR <= DHBR <= MHBR <= YHBR`
  - `WND <= DGST <= MGST <= YGST`
  - `DPCP <= MPCP <= YPCP`
  - `SUN <= DSUN <= MSUN <= YSUN`
  - `UVI <= DHUV <= MHUV <= YHUV`
  - Solar & UV must be `0.0` at night (astronomical elevation <= 0°).

## 3. Mandatory Git Workflow & GitHub Synchronization
- **Professional Portfolio Standard**: The repo `r0grr/homelab` is a public showcase; commits must be structured with Conventional Commits in Catalan/standard format.
- **Strictly No Secrets**: Never commit `.env`, passwords, tokens, API keys, or private router backups.
- **Feature Branching & Clean Merges**:
  1. Group changes logically and create a semantic branch (`feat/...`, `fix/...`, `style/...`).
  2. Push the branch to GitHub: `git push -u origin <branch>`
  3. Merge to `main` preserving the graph: `git checkout main && git merge --no-ff <branch> -m "merge: <description>"`
  4. Push `main` to GitHub: `git push origin main`
- Persistent reference skill: `/home/elbero/.gemini/config/skills/git-homelab-workflow/SKILL.md`.
