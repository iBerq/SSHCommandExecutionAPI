<?php
function executeCmdOnSSH($host, $username, $password, $command, &$output = null, &$error = null)
{
  $result = true;

  if (!($connection = ssh2_connect($host, 22))) {
    $error = "Can't connect to server.";
    $result = false;
    return $result;
  }

  if (!ssh2_auth_password($connection, $username, $password)) {
    ssh2_disconnect($connection);
    $error = "Authentication rejected by server.";
    $result = false;
    return $result;
  }

  $output = "";
  $error = "";

  if (!ssh2_run($connection, $command, $output, $error)) {
    $error = "Can't read response of the command.";
    $result = false;
  } else {
    $result = true;
  }

  ssh2_disconnect($connection);
  return $result;
}

function ssh2_run($ssh2, $cmd, &$out = null, &$err = null)
{
  $result = false;
  $out = '';
  $err = '';
  $sshout = ssh2_exec($ssh2, $cmd);
  if ($sshout) {
    $ssherr = ssh2_fetch_stream($sshout, SSH2_STREAM_STDERR);
    if ($ssherr) {
      # we cannot use stream_select() with SSH2 streams
      # so use non-blocking stream_get_contents() and usleep()
      if (stream_set_blocking($sshout, false) and stream_set_blocking($ssherr, false)) {
        $result = true;
        # loop until end of output on both stdout and stderr
        $wait = 0;
        while (!feof($sshout) or !feof($ssherr)) {
          # sleep only after not reading any data
          if ($wait)
            usleep($wait);
          $wait = 50000; # 1/20 second
          if (!feof($sshout)) {
            $one = stream_get_contents($sshout);
            if ($one === false) {
              $result = false;
              break;
            }
            if ($one != '') {
              $out .= $one;
              $wait = 0;
            }
          }
          if (!feof($ssherr)) {
            $one = stream_get_contents($ssherr);
            if ($one === false) {
              $result = false;
              break;
            }
            if ($one != '') {
              $err .= $one;
              $wait = 0;
            }
          }
        }
      }
      # we need to wait for end of command
      stream_set_blocking($sshout, true);
      stream_set_blocking($ssherr, true);
      # these will not get any output
      stream_get_contents($sshout);
      stream_get_contents($ssherr);
      fclose($ssherr);
    }
    fclose($sshout);
  }
  return $result;
}
