#!/usr/bin/env python3

import os
import sys
from email import policy
from email.parser import BytesParser

import dkim
import dns.resolver


def tags(value: str) -> dict[str, str]:
    return {
        key.strip().lower(): item.strip()
        for part in value.split(";")
        if "=" in part
        for key, item in [part.split("=", 1)]
    }


message = sys.stdin.buffer.read()
if not message:
    sys.exit("no message received on standard input")

resolver = dns.resolver.Resolver(configure=False)
resolver.nameservers = [os.environ.get("DNS_SERVER", "172.28.2.5")]


def lookup_txt(name: bytes, timeout: int = 5) -> bytes:
    answer = resolver.resolve(name.decode().rstrip("."), "TXT", lifetime=timeout)
    return b"".join(answer[0].strings)


parsed = BytesParser(policy=policy.SMTP).parsebytes(message)
dkim_header = parsed.get("DKIM-Signature")
arc_header = parsed.get("ARC-Message-Signature")
seal_header = parsed.get("ARC-Seal")
arc_auth_results = parsed.get("ARC-Authentication-Results")
auth_results = parsed.get("Authentication-Results")

if not dkim_header or not arc_header or not seal_header or not arc_auth_results or not auth_results:
    sys.exit("final DKIM, ARC, or Authentication-Results headers are missing")

dkim_tags = tags(str(dkim_header))
arc_tags = tags(str(arc_header))
seal_tags = tags(str(seal_header))

if (dkim_tags.get("d"), dkim_tags.get("s")) != ("agentj.test", "final"):
    sys.exit("unexpected final DKIM identity")
if (arc_tags.get("d"), arc_tags.get("s")) != (
    "arc.agentj.test",
    "arc-202608",
):
    sys.exit("unexpected ARC identity")
if "dkim-signature" not in arc_tags.get("h", "").lower().split(":"):
    sys.exit("ARC-Message-Signature does not cover DKIM-Signature")
if seal_tags.get("cv") != "none" or seal_tags.get("i") != "1":
    sys.exit("unexpected first ARC instance")
arc_auth_results_value = str(arc_auth_results).lower()
auth_results_value = str(auth_results).lower()
for value in (arc_auth_results_value, auth_results_value):
    if "auth.agentj.test" not in value:
        sys.exit("Authentication-Results does not retain Rspamd provenance")
    if "forged.example" in value:
        sys.exit("Authentication-Results retains a forged result")
    for result in ("dkim=pass", "spf=pass", "dmarc=pass"):
        if result not in value:
            sys.exit(f"Authentication-Results does not retain Rspamd {result}")

if not dkim.verify(message, dnsfunc=lookup_txt):
    sys.exit("final DKIM signature verification failed")

arc_result, _, arc_reason = dkim.arc_verify(message, dnsfunc=lookup_txt)
if arc_result != b"pass":
    sys.exit(f"ARC verification failed: {arc_reason}")

print("final DKIM and ARC signatures: valid")
