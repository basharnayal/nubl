param(
  [string]$DocsRepoUrl = "https://github.com/basharnayal/nubl-docs.git",
  [string]$DocsDir = "docs"
)

$ErrorActionPreference = "Stop"

function Write-Info([string]$msg) { Write-Host "[docs] $msg" -ForegroundColor Cyan }
function Write-Warn([string]$msg) { Write-Host "[docs] $msg" -ForegroundColor Yellow }

if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
  throw "git is required but was not found in PATH."
}

if (Test-Path $DocsDir) {
  if (Test-Path (Join-Path $DocsDir ".git")) {
    Write-Info "Updating existing docs repo in '$DocsDir'..."
    git -C $DocsDir pull --ff-only
    exit 0
  }

  $items = Get-ChildItem -Force $DocsDir -ErrorAction SilentlyContinue
  if ($items -and $items.Count -gt 0) {
    Write-Warn "Found a non-empty '$DocsDir' folder that is not a git repo. Aborting to avoid overwriting local files."
    Write-Warn "If you want to replace it, delete '$DocsDir' and re-run this script."
    exit 1
  }
}

Write-Info "Cloning docs repo into '$DocsDir'..."
git clone $DocsRepoUrl $DocsDir
Write-Info "Done."

