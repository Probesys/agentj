upstream "local" {
  self_scan = yes;
  settings_id = "auth";
}

allow_file_and_shm_inputs = false;
timeout = 120s;
