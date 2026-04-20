<?php

declare(strict_types=1);

namespace SSHFSMountTool\Connection;

enum ConnectionStatus: string {

  case Mounted = 'Mounted';
  case NotMounted = 'Not mounted';

}
