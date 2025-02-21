#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os
import socket
import threading

from db import DbConnectionPool
from handler import Handler


class Daemon:

    def __init__(self) -> None:
        self.socket_conf = ('0.0.0.0',10033)
        self.db_pool = DbConnectionPool()
        self.run()

    def run(self) -> None:
        """Run server"""
        self.open_socket()
        while True:
            try:
                conn, addr = self.socket.accept()
                threading.Thread(target=Handler, args=(conn, addr, self.db_pool)).start()
            except KeyboardInterrupt:
                break
        self.close_socket()

    def open_socket(self) -> None:
        """Open socket for communications"""
        socket_conf = self.socket_conf
        host, port = socket_conf[0], socket_conf[1]
        if ':' in host:
            self.bind_socket(socket.AF_INET6, (host, port))
        elif '.' in host:
            self.bind_socket(socket.AF_INET, (host, port))
        else:
            raise ValueError('Invalid socket configuration')
        print('socket opened')
        self.socket.listen(5)

    def bind_socket(self, family: int, address):
        """Bind socket"""
        self.socket = socket.socket(family, socket.SOCK_STREAM)
        self.socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        self.socket.bind(address)

    def close_socket(self) -> None:
        """Close socket"""
        self.socket.close()
        socket_conf = self.socket_conf
        if len(socket_conf) == 1:
            try:
                os.remove(socket_conf[0])
            except OSError as error:
                self.logger.error('run.py - Error removing socket file: %s', error)


if __name__ == '__main__':  # pragma: no cover
    Daemon()

