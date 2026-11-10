#!/usr/bin/env bash
# Remove only this workflow attempt's staged evidence and always clean runner temp files.
set -euo pipefail

if [[ "$#" -ne 8 ]]; then
  echo "remote_transfer_cleanup=FAIL reason=invalid_arguments" >&2
  exit 2
fi

ssh_host="$1"
ssh_user="$2"
ssh_port="$3"
key_file="$4"
known_hosts_file="$5"
run_id="$6"
run_attempt="$7"
runner_temp="$8"

transfer_id="${run_id}-${run_attempt}"
if [[ ! "$transfer_id" =~ ^[0-9]+-[0-9]+$ ]]; then
  echo "remote_transfer_cleanup=FAIL reason=invalid_transfer_id" >&2
  exit 2
fi

cleanup_runner_files() {
  rm -f -- "$key_file" "$known_hosts_file" \
    "$runner_temp/phase1c-hostinger-readonly-key" \
    "$runner_temp/phase1c-hostinger-known-hosts" \
    evidence.gz evidence.gz.part remote-evidence-manifest.json
}
trap cleanup_runner_files EXIT

if [[ "${REMOTE_TRANSFER_ATTEMPTED:-false}" != "true" ]]; then
  echo "remote_transfer_cleanup=NOT_REQUIRED reason=collector_not_started"
  exit 0
fi

if [[ -z "$ssh_host" || -z "$ssh_user" || ! "$ssh_port" =~ ^[0-9]+$ || ! -s "$key_file" || ! -s "$known_hosts_file" ]]; then
  echo "remote_transfer_cleanup=FAIL reason=ssh_cleanup_material_unavailable" >&2
  exit 1
fi

remote_prefix="/tmp/phase1c-readonly-evidence-$transfer_id"
remote_gzip="$remote_prefix.gz"
remote_manifest="$remote_prefix.manifest.json"
remote_command="rm -f -- '$remote_gzip' '$remote_manifest' && test ! -e '$remote_gzip' && test ! -e '$remote_manifest'"

if ssh -T -q -p "$ssh_port" -i "$key_file" \
  -o BatchMode=yes -o IdentitiesOnly=yes -o StrictHostKeyChecking=yes \
  -o "UserKnownHostsFile=$known_hosts_file" \
  "$ssh_user@$ssh_host" "$remote_command"; then
  echo "remote_transfer_cleanup=PASS verified_absent=2"
else
  echo "remote_transfer_cleanup=FAIL reason=remote_cleanup_or_absence_verification_failed" >&2
  exit 1
fi
