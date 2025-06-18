

class Handler:
    """Handle request"""

    db = None
    data = ''

    def __init__(self, conn: object, addr: str, db_pool: object):
        self.conn = conn
        self.addr = addr
        self.db_pool = db_pool
        try:
            self.handle()
        except Exception as e:  # pragma: no cover
            print('Unhandled Exception:', e)
            print('Received DATA:', self.data)
            self.send_response('DUNNO')  # use DUNNO as accept action, just to distinguish between OK and unhandled exception

    def handle(self) -> None:
        """Handle request"""
        # Read data
        # Attention: We only read first 2048 bytes, which is sufficient for our needs
        self.data = self.conn.recv(2048).decode('utf-8')
        if not self.data:
            raise Exception('No data received')
        #print('Received data:', self.data)


        # Parse data using a dictionary comprehension
        request = {
            key: value
            for line in self.data.strip().split("\n")
            for key, value in [line.strip().split('=', 1)] if value
        }

        # request['queue_id'],
        # request['sasl_username'],
        # request['client_address'],
        # request['client_name'],
        # request['recipient_count'],
        # request.get('sender'),
        # request.get('recipient'),
        # request.get('cc_address'),
        # request.get('bcc_address'),

        self.db = self.db_pool.connection()
        self.cursor = self.db.cursor()
        self.cursor.execute(
                '''
                select 1 from domain a
                join domain_relay b on a.id = b.domain_id
                join users c on a.id = c.domain_id
                where b.ip_address = %s and c.email = %s
                ''',
                (request['client_address'], request['sender'])
                )
        result = self.cursor.fetchone()
        if result is None:
            print('INVALID sender/ip pair:', request['client_address'], request['sender'])
            self.send_response('REJECT')
            return

        print('OK ', request['client_address'], request['sender'])
        self.send_response('OK')

        ## Detailed log message in the following format:
        ## TEST1234567: client=unknown[8.8.8.8], helo=myCLIENTPC, sasl_method=PLAIN, sasl_username=test@example.com,
        ## recipient_count=1, curr_count=2/1000, status=ACCEPTED
        #log_msg = 'client={}[{}], helo={}, sasl_method={}, sasl_username={}, from={}, to={}, recipient_count={}, curr_count={}/{}, status={}{}'.format(  # noqa: E501
        #    message.client_name,
        #    message.client_address,
        #    request.get('helo_name'),  # currently not stored in Message object or `messages` table
        #    request['sasl_method'],  # currently not stored in Message object or `messages` table
        #    message.sender,
        #    message.from_addr,
        #    message.to_addr,
        #    message.rcpt_count,
        #    message.ratelimit.rcpt_counter,
        #    message.ratelimit.quota,
        #    'BLOCKED' if blocked else 'ACCEPTED',
        #    ' (QUOTA_LIMIT_REACHED)' if blocked and not was_blocked else ''
        #)

    def send_response(self, message: str = 'OK') -> None:
        """Send response"""
        # actions return to Postfix, see http://www.postfix.org/access.5.html for a list of actions.
        data = 'action={}\n\n'.format(message)
        self.conn.send(data.encode('utf-8'))
        self.conn.close()
