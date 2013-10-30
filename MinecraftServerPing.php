<?php
        /*
         * Queries Minecraft server
         * Returns array on success, false on failure.
         *
         * WARNING: This is using an old "ping" feature, only use this to ping servers prior to 1.7 version.
         *
         * Written by xPaw
         * Added support for 1.6 servers and below
         *
         * Website: http://xpaw.ru
         * GitHub: https://github.com/xPaw/PHP-Minecraft-Query
         */
        
        function QueryMinecraft( $IP, $Port = 25565, $Timeout = 2 )
        {
                $Socket = Socket_Create( AF_INET, SOCK_STREAM, SOL_TCP );
                
                Socket_Set_Option( $Socket, SOL_SOCKET, SO_SNDTIMEO, array( 'sec' => (int)$Timeout, 'usec' => 0 ) );
                Socket_Set_Option( $Socket, SOL_SOCKET, SO_RCVTIMEO, array( 'sec' => (int)$Timeout, 'usec' => 0 ) );
                
                if( $Socket === FALSE || @Socket_Connect( $Socket, $IP, (int)$Port ) === FALSE )
                {
                        return FALSE;
                }
                
                Socket_Send( $Socket, "\xFE\x01", 2, 0 );
                $Len = Socket_Recv( $Socket, $Data, 512, 0 );
                Socket_Close( $Socket );
                
                if( $Len < 4 || $Data[ 0 ] !== "\xFF" )
                {	
		$Socket = Socket_Create( AF_INET, SOCK_STREAM, SOL_TCP );
		
		Socket_Set_Option( $Socket, SOL_SOCKET, SO_SNDTIMEO, array( 'sec' => (int)$Timeout, 'usec' => 0 ) );
		Socket_Set_Option( $Socket, SOL_SOCKET, SO_RCVTIMEO, array( 'sec' => (int)$Timeout, 'usec' => 0 ) );
		
		if( $Socket === FALSE || @Socket_Connect( $Socket, $IP, (int)$Port ) === FALSE )
		{
			return FALSE;
		}
		
		$Length = StrLen( $IP );
		$Data = Pack( 'cccca*', HexDec( $Length ), 0, 0x04, $Length, $IP ) . Pack( 'nc', $Port, 0x01 );
		
		Socket_Send( $Socket, $Data, StrLen( $Data ), 0 ); // handshake
		Socket_Send( $Socket, "\x01\x00", 2, 0 ); // status ping
		
		$Length = _QueryMinecraft_Read_VarInt( $Socket ); // full packet length
		
		if( $Length < 10 )
		{
			Socket_Close( $Socket );
			
			return FALSE;
		}
		
		Socket_Read( $Socket, 1 ); // packet type, in server ping it's 0
		
		$Length = _QueryMinecraft_Read_VarInt( $Socket ); // string length
		
		$Data = Socket_Read( $Socket, $Length, PHP_NORMAL_READ ); // and finally the json string
		
		Socket_Close( $Socket );
		
		$Data = JSON_Decode( $Data, true );
		
		return JSON_Last_Error( ) === JSON_ERROR_NONE ? $Data : FALSE;
	}
	
	function _QueryMinecraft_Read_VarInt( $Socket )
	{
		$i = 0;
		$j = 0;
		
		while( true )
		{
			$k = Ord( Socket_Read( $Socket, 1 ) );
			
			$i |= ( $k & 0x7F ) << $j++ * 7;
			
			if( $j > 5 )
			{
				throw new RuntimeException( 'VarInt too big' );
			}
			
			if( ( $k & 0x80 ) != 128 )
			{
				break;
			}
		}
		
		return $i;

                }
                
                $Data = SubStr( $Data, 3 ); // Strip packet header (kick message packet and short length)
                $Data = iconv( 'UTF-16BE', 'UTF-8', $Data );
                
                // Are we dealing with Minecraft 1.4+ server?
                if( $Data[ 1 ] === "\xA7" && $Data[ 2 ] === "\x31" )
                {
                        $Data = Explode( "\x00", $Data );
                        
                        return Array(
                                'HostName'   => $Data[ 3 ],
                                'Players'    => IntVal( $Data[ 4 ] ),
                                'MaxPlayers' => IntVal( $Data[ 5 ] ),
                                'Protocol'   => IntVal( $Data[ 1 ] ),
                                'Version'    => $Data[ 2 ]
                        );
                }
                
                $Data = Explode( "\xA7", $Data );
                
                return Array(
                        'HostName'   => SubStr( $Data[ 0 ], 0, -1 ),
                        'Players'    => isset( $Data[ 1 ] ) ? IntVal( $Data[ 1 ] ) : 0,
                        'MaxPlayers' => isset( $Data[ 2 ] ) ? IntVal( $Data[ 2 ] ) : 0,
                        'Protocol'   => 0,
                        'Version'    => '1.3'
                );
        }
