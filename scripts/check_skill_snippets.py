"""Static-analyse every PHP snippet in the mailchannels-php skill with PHPStan.

For each Markdown file under `.agents/skills/mailchannels-php/`:

1. Extract every fenced PHP code block (```php … ```).
2. Strip `use` import statements from each block — the preamble already
   declares every import the skill uses, and duplicate `use` statements
   are a PHP parse error. Stripped lines are dropped from line-number
   tracking (their originals still appear in the Markdown for readers).
3. Substitute the documentation placeholder `[...]` with `[]` so calls
   like `queue([...])` are valid PHP.
4. Concatenate the surviving block lines for that file into a single PHP
   source, preceded by a fixed preamble that provides:
   - `<?php` and `declare(strict_types=1)`.
   - Every `use` import referenced anywhere in the skill — SDK classes
     plus any framework classes (Symfony Mailer, Laravel Mail) used by
     the plugin integration docs.
   - Typed stub functions whose return types let PHPStan infer the correct
     static type for key variables without executing any code.
     See "Documentation Dummies" below.
5. Hand the assembled source to PHPStan at level `max` and report
   failures, attributing errors back to the source Markdown file.

Exit codes: `0` — all snippets pass; `1` — at least one snippet failed;
`2` — configuration error (missing files, PHPStan not installed).

## Documentation Dummies

The preamble provides stub functions whose PHPDoc return types tell
PHPStan what type each variable has. Only variables used across snippets
without prior assignment need stubs; variables that snippets assign first
are typed by PHPStan automatically from the right-hand-side expression.

Typed stubs (PHPStan infers the full SDK type from the return annotation):
- `$client`     — `MailChannels\\Client`; every resource access is checked.
- `$params`     — `MailChannels\\Message\\EmailParams`.
- `$baseConfig` — `MailChannels\\Config`.
- `$logger`     — `Psr\\Log\\LoggerInterface`.
- `$result`     — `MailChannels\\Response\\SendResult`.

Everything else is assigned a permissive null/empty placeholder so
PHPStan does not flag them as undeclared. Because PHPStan tracks types
through flow analysis, a snippet that reassigns any of these variables
gets the correct inferred type from that assignment onwards — the
placeholder is never seen at the point of use.

## Suppressed PHPStan Identifiers

Some PHPStan errors are intentionally suppressed in the output parser
rather than worked around in the documentation:

- `expr.resultUnused` — PHPStan flags bare property-access expressions
  like `$response->statusCode;` that appear on their own line and whose
  value is not used.  Documentation blocks that enumerate available
  properties use exactly this pattern; suppressing here lets the docs
  remain readable without littering them with `$_ =` assignments.

Run with `--show-source` to print the assembled PHP before checking;
useful when an error message points at a line you'd like to inspect.
"""

from __future__ import annotations

import argparse
import logging
import re
import subprocess
import sys
import tempfile
from dataclasses import dataclass
from pathlib import Path

logger = logging.getLogger(__name__)

ROOT = Path(__file__).resolve().parents[1]
SKILL_DIR = ROOT / ".agents" / "skills" / "mailchannels-php"
PHPSTAN_BIN = ROOT / "vendor" / "bin" / "phpstan"
PHPSTAN_LEVEL = "max"

# PHPStan errors that are intentionally suppressed.
# Each entry is a (identifier, message_fragment) pair — a line is suppressed
# when EITHER the identifier or the message fragment matches. The message
# fragment is the fallback for PHPStan builds that omit the identifier tag.
# See module docstring for the rationale behind each entry.
SUPPRESSED_ERRORS: list[tuple[str, str]] = [
    (
        "expr.resultUnused",
        "on a separate line does not do anything",
    ),
]

PHP_BLOCK = re.compile(
    r"^```php[ \t]*\n(?P<body>.*?)\n^```[ \t]*$",
    re.DOTALL | re.MULTILINE,
    )

# Documentation placeholder for an array payload — e.g. `queue([...])`.
# Substituted with `[]` so PHPStan sees valid syntax.
PLACEHOLDER_ARRAY = re.compile(r"\[\.\.\.\]")

# Auto-injected scaffolding prepended to every assembled snippet group.
# Declares every `use` import and every documentation dummy described in
# the module docstring. Keep this in sync with that docstring.
PREAMBLE = r'''<?php

declare(strict_types=1);

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Support\Facades\Mail;
use MailChannels\Client;
use MailChannels\Config;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use MailChannels\Enum\CustomTrackingScope;
use MailChannels\Enum\CustomTrackingStatus;
use MailChannels\Enum\DkimAlgorithm;
use MailChannels\Enum\DkimKeyStatus;
use MailChannels\Enum\DkimUpdateStatus;
use MailChannels\Enum\MetricsInterval;
use MailChannels\Enum\MetricsSenderType;
use MailChannels\Enum\MetricsSortOrder;
use MailChannels\Enum\SuppressionDeleteSource;
use MailChannels\Enum\SuppressionSource;
use MailChannels\Enum\WebhookBatchStatus;
use MailChannels\Exception\AuthenticationException;
use MailChannels\Exception\BadGatewayException;
use MailChannels\Exception\ConflictException;
use MailChannels\Exception\ForbiddenException;
use MailChannels\Exception\InvalidRequestException;
use MailChannels\Exception\MailChannelsException;
use MailChannels\Exception\PayloadTooLargeException;
use MailChannels\Exception\RateLimitException;
use MailChannels\Exception\ServerException;
use MailChannels\Exception\TransportException;
use MailChannels\Exception\ValidationException;
use MailChannels\Message\Attachment;
use MailChannels\Message\Content;
use MailChannels\Message\EmailAddress;
use MailChannels\Message\EmailParams;
use MailChannels\Message\Personalization;
use MailChannels\Response\SendResult;
use MailChannels\Webhook\Verifier;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\Psr18Client;

// ---------------------------------------------------------------------------
// Typed stub functions — PHPStan infers variable types from return types.
// These functions are never called; they only exist for static analysis.
// ---------------------------------------------------------------------------
/** @return Client */ function __stub_client(): Client { throw new \LogicException('stub'); }
/** @return Config */ function __stub_config(): Config { throw new \LogicException('stub'); }
/** @return EmailParams */ function __stub_params(): EmailParams { throw new \LogicException('stub'); }
/** @return LoggerInterface */ function __stub_logger(): LoggerInterface { throw new \LogicException('stub'); }
/** @return SendResult */ function __stub_result(): SendResult { throw new \LogicException('stub'); }

$client     = __stub_client();
$baseConfig = __stub_config();
$params     = __stub_params();
$logger     = __stub_logger();
$result     = __stub_result();

// Everything else: permissive null/empty so PHPStan does not flag
// undeclared variables. Snippets that use these will typically
// reassign them, after which PHPStan infers the correct type.
$response = $key = $sub = $page = null;
$created = $keys = $passwords = $batches = $entries = $preview = null;
$usage = $checks = $tenantClient = $tenantConfig = null;
$parentUsage = $subUsage = $invoice = $logo = $attachment = null;
$data = $factory = null;
$rawBody = $signatureBase = $signatureBytes = $publicKey = '';
$keyId = $requestUri = $tenantApiKey = $tenantKey = $pdfBytes = $passwordId = '';
$headers = $m = [];
$e = new \RuntimeException();
'''

@dataclass(frozen=True)
class Block:
    """PHP code block extracted from a Markdown file."""
    body: str
    """1-based Markdown line of the first line *inside* the ```php fence."""
    start_line: int


@dataclass(frozen=True)
class SourceLine:
    """Line of assembled PHP with its origin in the source Markdown."""
    text: str
    """1-based Markdown source line, or None for synthetic scaffolding."""
    md_line: int | None


@dataclass(frozen=True)
class AssembledSource:
    """Synthesized PHP source for one Markdown file, with a line-origin map."""
    source: str
    """1-based PHP line → 1-based Markdown line, or None for synthetic lines."""
    php_to_md: tuple[int | None, ...]


@dataclass(frozen=True)
class LintResult:
    """Outcome of running PHPStan on one assembled snippet group."""
    returncode: int
    output: str


_SUPPRESSED_RE = re.compile(
    r"\[identifier=(?:" + "|".join(re.escape(ident) for ident, _ in SUPPRESSED_ERRORS) + r")\]"
    + r"|"
    + "|".join(re.escape(msg) for _, msg in SUPPRESSED_ERRORS)
)


def filter_suppressed(output: str) -> str:
    """Strip lines matching any suppressed identifier or message fragment."""
    if len(SUPPRESSED_ERRORS) < 1:
        return output

    return "\n".join(
        line for line in output.splitlines() if not _SUPPRESSED_RE.search(line)
    )


def is_use_statement(line: str) -> bool:
    """Return True when a line is a single-line PHP class-import `use` declaration.

    Handles ``use Foo\\Bar;``, ``use Foo\\Bar as Baz;``, and
    ``use Foo\\{Bar, Baz};``.  Multi-line grouped imports (``use Foo\\{``
    … ``};``) are handled separately in ``assemble()`` via a state flag.
    """
    stripped = line.strip()
    return stripped.startswith("use ") and stripped.endswith(";")


def starts_multiline_use(line: str) -> bool:
    """Return True when a line opens a multi-line grouped import (``use Foo\\{``)."""
    stripped = line.strip()
    return stripped.startswith("use ") and stripped.endswith("{")


def extract_blocks(markdown: str) -> list[Block]:
    """
    `Block.start_line` points at the first line *inside* the opening
    ```` ```php ```` fence — the same line PHPStan reports when it
    finds an error on the snippet's first line.
    """
    blocks: list[Block] = []
    for match in PHP_BLOCK.finditer(markdown):
        body = match.group("body")
        opening_fence_line = markdown.count("\n", 0, match.start()) + 1
        blocks.append(Block(body=body, start_line=opening_fence_line + 1))
    return blocks


def assemble(blocks: list[Block]) -> AssembledSource:
    """Concatenate blocks into one PHP source plus a php→markdown line map.

    The result has two layers:

    1. The fixed PREAMBLE (``<?php``, ``declare``, all ``use`` imports,
       stub functions, dummy variables) — every line gets ``md_line=None``.
    2. The body assembled from each block's non-``use`` lines, with
       ``md_line`` pointing back to the originating Markdown line.
       ``use`` lines are stripped because the preamble already declares
       every import, and duplicate ``use`` statements are a PHP parse error.
       A blank separator line (``md_line=None``) is inserted between blocks.

    ``AssembledSource.php_to_md[i]`` is the 1-based Markdown line for
    PHP line ``i`` (1-based), or ``None`` for synthetic lines.
    """
    lines: list[SourceLine] = []

    # Layer 1 — preamble (always at the top).
    for preamble_line in PREAMBLE.splitlines():
        lines.append(SourceLine(text=preamble_line, md_line=None))

    # Layer 2 — block bodies with use statements stripped.
    for index, block in enumerate(blocks):
        if index > 0:
            lines.append(SourceLine(text="", md_line=None))
        block_lines = PLACEHOLDER_ARRAY.sub("[]", block.body).splitlines()
        in_multiline_use = False
        for offset, text in enumerate(block_lines):
            if in_multiline_use:
                # Inside a multi-line grouped import — skip until closing };
                if text.strip() in ("};", "} ;"):
                    in_multiline_use = False
                logger.debug("  strip use (cont): %s", text.strip())
                continue
            if starts_multiline_use(text):
                in_multiline_use = True
                logger.debug("  strip use (open): %s", text.strip())
                continue
            if is_use_statement(text):
                # Drop from assembled source; the preamble covers it.
                logger.debug("  strip use: %s", text.strip())
                continue
            lines.append(SourceLine(text=text, md_line=block.start_line + offset))

    source = "\n".join(line.text for line in lines) + "\n"
    php_to_md: tuple[int | None, ...] = (None, *(line.md_line for line in lines))
    return AssembledSource(source=source, php_to_md=php_to_md)


def rewrite_line_numbers(
    output: str,
    label: str,
    php_to_md: tuple[int | None, ...],
) -> str:
    """Substitute synthetic PHP line numbers with their Markdown sources.
    PHPStan with ``--error-format=raw`` emits one error per line::
        /path/to/file.php:35:Unknown parameter $sttus ... [identifier]
    After path substitution this becomes::
        <label>:35:Unknown parameter $sttus ...
    This rewrites ``:35:`` to the originating Markdown line number.
    """
    pattern = re.compile(
        rf"^({re.escape(label)}):(\d+):", re.MULTILINE
    )

    def replacement(match: re.Match[str]) -> str:
        php_line = int(match.group(2))
        if 0 < php_line < len(php_to_md):
            md_line = php_to_md[php_line]
            if md_line is not None:
                return f"{match.group(1)}:{md_line}:"
        return f"{match.group(1)}:?(php {php_line}):"

    return pattern.sub(replacement, output)


def run_phpstan(source: str, label: str) -> LintResult:
    """Write source to a temp file, run PHPStan, return the result.

    Only stdout is used for error reporting — PHPStan sends the actual
    ``file:line:message`` errors there and routes informational noise
    (config file notes, instruction preambles) to stderr.
    """
    if not PHPSTAN_BIN.is_file():
        return LintResult(
            returncode=2,
            output="vendor/bin/phpstan not found — run `composer install`\n",
        )

    with tempfile.NamedTemporaryFile(
        mode="w",
        suffix=".php",
        delete=False,
        dir=ROOT,  # must be inside the project so PHPStan discovers phpstan.neon
    ) as handle:
        handle.write(source)
        tmp_path = Path(handle.name)
    try:
        completed = subprocess.run(
            [
                str(PHPSTAN_BIN),
                "analyse",
                f"--level={PHPSTAN_LEVEL}",
                "--error-format=raw",
                "--no-progress",
                "--memory-limit=512M",
                str(tmp_path),
            ],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            cwd=ROOT,
            check=False,
        )
        # Errors are on stdout in `file:line:message` format.
        # Rewrite both the absolute path and the basename so the label
        # substitution works regardless of which form PHPStan emits.
        output = completed.stdout
        output = output.replace(str(tmp_path), label)
        output = output.replace(tmp_path.name, label)
        output = filter_suppressed(output)
        # If suppression removed every error, treat it as a clean run.
        returncode = 0 if output.strip() == "" else completed.returncode
        return LintResult(returncode=returncode, output=output)
    finally:
        tmp_path.unlink(missing_ok=True)


def discover_markdown() -> list[Path]:
    """Return SKILL.md plus every resource Markdown file, in stable order.

    Resource files are discovered recursively, so Markdown nested under
    subdirectories of ``resources/`` (e.g. ``resources/plugins/``) is
    included alongside the top-level resource files.
    """
    files: list[Path] = []
    skill_md = SKILL_DIR / "SKILL.md"
    if skill_md.is_file():
        files.append(skill_md)
    resources = SKILL_DIR / "resources"
    if resources.is_dir():
        files.extend(
            sorted(resources.rglob("*.md"), key=lambda p: p.relative_to(resources).parts)
        )
    return files


def resolve_single_file(raw: str) -> Path | None:
    """Try to resolve a user-supplied file argument to an absolute path.

    Accepts paths relative to the repo root, the skill directory, or the
    skill's ``resources/`` subdirectory — whichever resolves first.
    Returns ``None`` if nothing matches.
    """
    candidates = [
        Path(raw) if Path(raw).is_absolute() else ROOT / raw,
        SKILL_DIR / raw,
        SKILL_DIR / "resources" / raw,
        ]
    for candidate in candidates:
        if candidate.is_file():
            return candidate
    return None


def parse_args(argv: list[str] | None) -> argparse.Namespace:
    """Parse command-line arguments."""
    parser = argparse.ArgumentParser(
        description=(
            f"PHPStan level-{PHPSTAN_LEVEL} analysis of PHP snippets "
            "embedded in the mailchannels-php skill."
        )
    )
    parser.add_argument(
        "file",
        nargs="?",
        metavar="FILE",
        help=(
            "Check only this Markdown file (relative to the repo root or "
            "the skill directory). Omit to check all files."
        ),
    )
    parser.add_argument(
        "--show-source",
        action="store_true",
        help="Print the assembled PHP source for each file before checking.",
    )
    parser.add_argument(
        "-v",
        "--verbose",
        action="store_true",
        help="Log debug-level messages (e.g. skipped / stripped lines).",
    )
    return parser.parse_args(argv)


def main(argv: list[str] | None = None) -> int:
    """Run PHPStan against every snippet under the mailchannels-php skill."""
    args = parse_args(argv)
    logging.basicConfig(
        level=logging.DEBUG if args.verbose else logging.INFO,
        format="%(message)s",
    )

    if not SKILL_DIR.is_dir():
        logger.error("Skill directory not found: %s", SKILL_DIR)
        return 2

    if args.file:
        resolved = resolve_single_file(args.file)
        if resolved is None:
            logger.error("File not found: %s", args.file)
            return 2
        md_files = [resolved]
    else:
        md_files = discover_markdown()

    if not md_files:
        logger.error("No Markdown files found under %s", SKILL_DIR)
        return 2

    total_blocks = 0
    failed: list[str] = []

    for md in md_files:
        rel = md.relative_to(ROOT)
        all_blocks = extract_blocks(md.read_text(encoding="utf-8"))
        if not all_blocks:
            logger.info("[skip] %s — no PHP blocks", rel)
            continue
        total_blocks += len(all_blocks)
        assembled = assemble(all_blocks)
        if args.show_source:
            logger.info(
                "--- assembled source for %s ---\n%s---", rel, assembled.source
            )
        lint_result = run_phpstan(assembled.source, str(rel))
        output = rewrite_line_numbers(
            lint_result.output, str(rel), assembled.php_to_md
        )
        if lint_result.returncode == 0:
            logger.info("[ ok ] %s (%d block%s)",
                        rel, len(all_blocks), "" if len(all_blocks) == 1 else "s")
        elif lint_result.returncode == 1:
            failed.append(str(rel))
            logger.error("[FAIL] %s (%d block%s)\n%s",
                         rel, len(all_blocks), "" if len(all_blocks) == 1 else "s",
                         output.rstrip() or "(no output)")
        else:
            return lint_result.returncode

    logger.info(
        "\nChecked %d file%s / %d block%s. %d failed.",
        len(md_files),
        "" if len(md_files) == 1 else "s",
        total_blocks,
        "" if total_blocks == 1 else "s",
        len(failed),
    )
    return 1 if failed else 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv[1:]))
