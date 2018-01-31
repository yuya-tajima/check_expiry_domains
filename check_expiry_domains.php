<?php
/**
 * 更新期限の近づいているドメインをお知らせする。
 *
 * インタプリタ: PHP 5.6以上
 *
 * 引数: なし
 *
 * 前提条件:
 *
 *   バリュードメインをチェックする場合
 *   呼び出すプロセスの環境変数VALUE_DOMAIN_USERにバリュードメインのユーザー名を指定しておく
 *   呼び出すプロセスの環環境変数VALUE_DOMAIN_PASSにバリュードメインのパスワード名を指定しておく
 *
 *   gonbei.jpをチェックする場合
 *   呼び出すプロセスの環境変数GONBEI_USERにgonbei.jpのユーザー名を指定しておく
 *   呼び出すプロセスの環環境変数GONBEI_PASSにgonbei.jpのパスワード名を指定しておく
 *
 *   お名前.comをチェックする場合
 *   呼び出すプロセスの環境変数ONAMAE_USERにお名前.comのユーザー名を指定しておく
 *   呼び出すプロセスの環環境変数ONAMAE_PASSにお名前.comのパスワード名を指定しておく
 *
 *   さくらインターネットをチェックする場合
 *   呼び出すプロセスの環境変数SAKURA_USERにさくらインターネットのユーザー名を指定しておく
 *   呼び出すプロセスの環環境変数SAKURA_PASSにさくらインターネットのパスワード名を指定しておく
 *
 *   ※ ログインに二段階認証を適用している場合は利用できません。
 *   ※ あまりにもログインに失敗するとIPで拒否されるケースを確認。
 *
 * 結果:
 *   ドメインの一覧と更新期限を出力する
 *
 */

function exe_check_expiry_domains() {

	$alert_interval  = 30;
	$today_obj       = new DateTime(date('Y-m-d'));
	$max_retry_count = 3;

	$check_urls = [];

	if ( getenv('VALUE_DOMAIN_USER') && getenv('VALUE_DOMAIN_PASS') ) {
		$check_urls['value-domain'] = [
			'login'      => 'https://www.value-domain.com/login.php'
			,'page'      => 'https://www.value-domain.com/extdom.php'
			,'post_data' => [
				'username'  => getenv('VALUE_DOMAIN_USER')
				,'password' => getenv('VALUE_DOMAIN_PASS')
				,'action'   => 'login2'
			]
		];
	}

	if ( getenv('GONBEI_USER') && getenv('GONBEI_PASS') ) {
		$check_urls['gonbei'] = [
			'login' => 'https://ias.il24.net/register/login.cgi'
			,'page' => 'https://ias.il24.net/mymenu/new.cgi'
			,'post_data' => [
				'LOGINID'   => getenv('GONBEI_USER')
				,'PASSWORD' => getenv('GONBEI_PASS')
				,'TEMPLATE' => ''
				,'login'    => ''
			]
		];
	}

	if ( getenv('ONAMAE_USER') && getenv('ONAMAE_PASS') ) {
		$check_urls['onamae.com'] = [
			'login' => 'https://www.onamae.com/domain/navi/domain.html'
			,'page' => 'https://www.onamae.com/domain/navi/domain'
			,'post_data' => [
				'username'   => getenv('ONAMAE_USER')
				,'password'  => getenv('ONAMAE_PASS')
			]
		];
	}

	if ( getenv('SAKURA_USER') && getenv('SAKURA_PASS') ) {
		$check_urls['sakura-internet'] = [
			'login' => 'https://secure.sakura.ad.jp/auth/login'
			,'page' => 'https://secure.sakura.ad.jp/menu/service/?mode=SD1010&ac=init'
			,'post_data' => [
				'memberLogin[membercd]'  => getenv('SAKURA_USER')
				,'memberLogin[password]' => getenv('SAKURA_PASS')
			]
		];
	}

	$soon_expires = [];
	$late_expires = [];

	foreach ( $check_urls as $service => $urls ) {

		$expires = [];

		$retry_count = 0;
		$is_login    = false;
		while ( ! $is_login && ( ++$retry_count <= $max_retry_count ) ) {
			$ch = curl_init();
			$cookie_path = '/tmp/' . $service;
			curl_setopt( $ch, CURLOPT_URL,            $urls['login'] );
			curl_setopt( $ch, CURLOPT_POSTFIELDS,     http_build_query( $urls['post_data'] ) );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie_path );

			$response = curl_exec( $ch );

			if ( $errno  = curl_errno( $ch ) ) {
				$error_message = curl_strerror( $errno );
				fwrite( STDERR, sprintf( 'curl error: error code %d. message %s. url %s.', $errno, $error_message, $url ) . PHP_EOL );
				curl_close( $ch );
			} else {
				$httpcode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
				curl_close( $ch );
				if ( file_exists( $cookie_path ) ) {
					$is_login = true;
				} else {
					if ( $httpcode >= 400 ) {
						fwrite( STDERR, sprintf( 'ERROR: %s Login failed. HTTP STATUS CODE is %d', $service, $httpcode ) . PHP_EOL );
						break;
					} else {
						fwrite( STDERR, sprintf( 'WARNING: %s Login failed. retry count %d...', $service, $retry_count ) . PHP_EOL );
						sleep(5);
					}
				}
			}
		}

		if ( ! $is_login ) {
			fwrite( STDERR, sprintf( 'ERROR: %s Login failed.', $service ) . PHP_EOL );
			fwrite( STDERR, PHP_EOL );
			continue;
		} else {
			fwrite( STDERR, sprintf( 'INFO: Maybe %s Login Success.', $service ) . PHP_EOL );
			fwrite( STDERR, PHP_EOL );
		}

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL,            $urls['page'] );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_COOKIEFILE,     $cookie_path );
		$response = curl_exec( $ch );
		if( $errno = curl_errno( $ch ) ) {
			$error_message = curl_strerror( $errno );
			fwrite( STDERR, sprintf( 'curl error: error code %d. message %s. url %s.', $errno, $error_message, $url ) . PHP_EOL );
		} else {
			$httpcode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			if ( $response ) {
				switch ( $service ) {
					case 'value-domain':
						$doc = new DOMDocument();
						libxml_use_internal_errors( true );
						$doc->loadHTML( $response );
						libxml_clear_errors();
						$xpath = new DOMXPath($doc);
						$nodes = $xpath->query('//table/tr[position()>1]');
						foreach ( $nodes as $node  ) {
							$node_value  = preg_replace( '#[ \t\r\n]+#', ' ', $node->nodeValue );
							$arr         = explode(' ', $node_value );
							$arr         = array_values(array_filter($arr));
							$domain      = $arr[0];
							$expire_date = $arr[1];
							$expires[]   = [$domain, $expire_date];
						}
						break;
					case 'gonbei':
						$doc = new DOMDocument();
						libxml_use_internal_errors( true );
						$doc->loadHTML( $response );
						libxml_clear_errors();
						$xpath = new DOMXPath($doc);
						$nodes = $xpath->query('//table/tr/td/table/tr/td/table/tr/td/table/tr/td/table/tr/td/table/tr');
						foreach ( $nodes as $node ) {
							$node_value = preg_replace( '#[ \t\r\n]+#', ' ', $node->nodeValue );
							$node_value = trim($node_value);
							if ( preg_match('#ドメイン取得サービス.*契約中.*#', $node_value, $m )) {
								$arr = explode(' ', $m[0] );
								$arr = array_values(array_filter($arr));
								if ( count($arr) <= 5 ) {
									array_splice( $arr, 0, -3 );
									$domain = $arr[0];
									$expire_date = str_replace( ['迄', '/'], ['', '-'], $arr[2] );
									$expires[] = [$domain, $expire_date];
								}
							}
						}
						break;
					case 'onamae.com':
						$doc = new DOMDocument();
						libxml_use_internal_errors( true );
						$doc->loadHTML( $response );
						libxml_clear_errors();
						$xpath = new DOMXPath($doc);
						$nodes = $xpath->query('//table/tr[position()>1]');
						foreach ( $nodes as $node ) {
							$node_value = preg_replace( '#[ \t\r\n]+#', ' ', $node->nodeValue );
							$node_value = trim($node_value);
							$arr = explode(' ', $node_value );
							$arr = array_values(array_filter($arr));
							$domain      = $arr[0];
							$expire_date = str_replace( ['/'], ['-'], $arr[1] );
							$expires[]   = [$domain, $expire_date];
						}
						break;
					case 'sakura-internet':
						$doc = new DOMDocument();
						libxml_use_internal_errors( true );
						$doc->loadHTML( $response );
						libxml_clear_errors();
						$xpath = new DOMXPath($doc);
						$nodes = $xpath->query('//table[@class="frame"]/tr[position()>1]');
						foreach ( $nodes as $node ) {
							$node_value = preg_replace( '#[ \t\r\n]+#', ' ', $node->nodeValue );
							$node_value = trim($node_value);
							$arr = explode(' ', $node_value );
							$arr = array_values(array_filter($arr));
							$domain = preg_replace( '#[0-9]+\z#', '', $arr[0] );
							$expire_date = str_replace( ['年', '月', '日'], ['-', '-', ''], $arr[1] );
							$expires[]   = [$domain, $expire_date];
						}
						break;
				}

				foreach ( $expires as $e ) {
					$expire_date_obj = new DateTime( $e[1] );
					$interval        = $expire_date_obj->diff( $today_obj );
					$expire_days     = $interval->format('%a');
					if ( $alert_interval > (int) $expire_days ) {
						$soon_expires[$service][] = [ $e[0], $e[1], $expire_days ];
					} else {
						$late_expires[$service][] = [ $e[0], $e[1], $expire_days ];
					}
				}

			}
		}

		curl_close( $ch );

		if ( file_exists( $cookie_path ) ) {
			unlink( $cookie_path );
		}
	}

	if ( $soon_expires ) {
		fwrite( STDOUT, sprintf( 'WARNING: The expiry date is approaching.' ) . PHP_EOL );
		fwrite( STDOUT, PHP_EOL );
		foreach ( $soon_expires as $service => $_soon_expires ) {
			fwrite( STDOUT, sprintf( 'Domain service is %s', $service ) . PHP_EOL );
			foreach ( $_soon_expires as $e ) {
				fwrite( STDOUT, sprintf( '%s will expire in %d days. expiry date is %s.', $e[0], $e[2], $e[1] ) . PHP_EOL );
			}
		}
		fwrite( STDOUT, PHP_EOL );
	}

	if ( $late_expires ) {
		fwrite( STDOUT, sprintf( 'INFO: There are over %d days until the expiry date.', $alert_interval ) . PHP_EOL );
		fwrite( STDOUT, PHP_EOL );
		foreach ( $late_expires as $service => $_late_expires ) {
			fwrite( STDOUT, sprintf( 'Domain service is %s', $service ) . PHP_EOL );
			foreach ( $_late_expires as $e ) {
				fwrite( STDOUT, sprintf( '%s %s', $e[0], $e[1] ) . PHP_EOL );
			}
			fwrite( STDOUT, PHP_EOL );
		}
	} else {
		fwrite( STDOUT, sprintf( 'NOTICE: All of the access might have been denied.' ) . PHP_EOL );
	}
}

exe_check_expiry_domains();
