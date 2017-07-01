<?php
/**
 * Plugin Name: 123PAY.IR - Restrict Content Pro
 * Description: پلاگین پرداخت، سامانه پرداخت یک دو سه پی برای Restrict Content Pro
 * Plugin URI: https://123pay.ir
 * Author: تیم فنی یک دو سه پی
 * Author URI: http://123pay.ir
 * Version: 1.0
 **/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once( '_123pay_session.php' );
if ( ! class_exists( 'RCP__123pay' ) ) {
	class RCP__123pay {
		public function __construct() {
			add_action( 'init', array( $this, '_123pay_Verify' ) );
			add_action( 'rcp_payments_settings', array( $this, '_123pay_Setting' ) );
			add_action( 'rcp_gateway__123pay', array( $this, '_123pay_Request' ) );
			add_filter( 'rcp_payment_gateways', array( $this, '_123pay_Register' ) );
			if ( ! function_exists( 'RCP_IRAN_Currencies_By_123PAY' ) && ! function_exists( 'RCP_IRAN_Currencies' ) ) {
				add_filter( 'rcp_currencies', array( $this, 'RCP_IRAN_Currencies' ) );
			}
		}

		public function RCP_IRAN_Currencies( $currencies ) {
			unset( $currencies['RIAL'] );
			$currencies['تومان'] = __( 'تومان', 'rcp__123pay' );
			$currencies['ریال']  = __( 'ریال', 'rcp__123pay' );

			return $currencies;
		}

		public function _123pay_Register( $gateways ) {
			global $rcp_options;

			if ( version_compare( RCP_PLUGIN_VERSION, '2.1.0', '<' ) ) {
				$gateways['_123pay'] = isset( $rcp_options['_123pay_name'] ) ? $rcp_options['_123pay_name'] : __( 'سامانه پرداخت یک دو سه پی', 'rcp__123pay' );
			} else {
				$gateways['_123pay'] = array(
					'label'       => isset( $rcp_options['_123pay_name'] ) ? $rcp_options['_123pay_name'] : __( 'سامانه پرداخت یک دو سه پی', 'rcp__123pay' ),
					'admin_label' => isset( $rcp_options['_123pay_name'] ) ? $rcp_options['_123pay_name'] : __( 'سامانه پرداخت یک دو سه پی', 'rcp__123pay' ),
				);
			}

			return $gateways;
		}

		public function _123pay_Setting( $rcp_options ) {
			?>
            <hr/>
            <table class="form-table">
				<?php do_action( 'RCP__123pay_before_settings', $rcp_options ); ?>
                <tr valign="top">
                    <th colspan=2><h3><?php _e( 'تنظیمات سامانه پرداخت یک دو سه پی', 'rcp__123pay' ); ?></h3>
                    </th>
                </tr>
                <tr valign="top">
                    <th>
                        <label for="rcp_settings[_123pay_merchant_id]"><?php _e( 'کد پذیرنده', 'rcp__123pay' ); ?></label>
                    </th>
                    <td>
                        <input class="regular-text" id="rcp_settings[_123pay_merchant_id]" style="width: 300px;"
                               name="rcp_settings[_123pay_merchant_id]"
                               value="<?php if ( isset( $rcp_options['_123pay_merchant_id'] ) ) {
							       echo $rcp_options['_123pay_merchant_id'];
						       } ?>"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th>
                        <label for="rcp_settings[_123pay_query_name]"><?php _e( 'نام لاتین درگاه', 'rcp__123pay' ); ?></label>
                    </th>
                    <td>
                        <input class="regular-text" id="rcp_settings[_123pay_query_name]" style="width: 300px;"
                               name="rcp_settings[_123pay_query_name]"
                               value="<?php echo isset( $rcp_options['_123pay_query_name'] ) ? $rcp_options['_123pay_query_name'] : '_123pay'; ?>"/>
                        <div class="description"><?php _e( 'این نام در هنگام بازگشت از بانک در آدرس بازگشت از بانک نمایان خواهد شد . از به کاربردن حروف زائد و فاصله جدا خودداری نمایید .', 'rcp__123pay' ); ?></div>
                    </td>
                </tr>
                <tr valign="top">
                    <th>
                        <label for="rcp_settings[_123pay_name]"><?php _e( 'نام نمایشی درگاه', 'rcp__123pay' ); ?></label>
                    </th>
                    <td>
                        <input class="regular-text" id="rcp_settings[_123pay_name]" style="width: 300px;"
                               name="rcp_settings[_123pay_name]"
                               value="<?php echo isset( $rcp_options['_123pay_name'] ) ? $rcp_options['_123pay_name'] : __( 'سامانه پرداخت یک دو سه پی', 'rcp__123pay' ); ?>"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th>
                        <label><?php _e( 'تذکر ', 'rcp__123pay' ); ?></label>
                    </th>
                    <td>
                        <div class="description"><?php _e( 'از سربرگ مربوط به ثبت نام در تنظیمات افزونه حتما یک برگه برای بازگشت از بانک انتخاب نمایید . ترجیحا نامک برگه را لاتین قرار دهید .<br/> نیازی به قرار دادن شورت کد خاصی در برگه نیست و میتواند برگه ی خالی باشد .', 'rcp__123pay' ); ?></div>
                    </td>
                </tr>
				<?php do_action( 'RCP__123pay_after_settings', $rcp_options ); ?>
            </table>
			<?php

		}

		public function _123pay_Request( $subscription_data ) {
			global $rcp_options;
			ob_start();
			$query                = isset( $rcp_options['_123pay_query_name'] ) ? $rcp_options['_123pay_query_name'] : '_123pay';
			$amount               = str_replace( ',', '', $subscription_data['price'] );
			$_123pay_payment_data = array(
				'user_id'           => $subscription_data['user_id'],
				'subscription_name' => $subscription_data['subscription_name'],
				'subscription_key'  => $subscription_data['key'],
				'amount'            => $amount
			);

			$__123pay_session = _123PAY_Session::get_instance();
			@session_start();
			$__123pay_session['_123pay_payment_data'] = $_123pay_payment_data;
			$_SESSION["_123pay_payment_data"]         = $_123pay_payment_data;
			do_action( 'RCP_Before_Sending_to__123pay', $subscription_data );
			if ( $rcp_options['currency'] == 'تومان' || $rcp_options['currency'] == 'TOMAN' || $rcp_options['currency'] == 'تومان ایران' || $rcp_options['currency'] == 'Iranian Toman (&#65020;)' ) {
				$amount = $amount * 10;
			}

			$Price      = intval( $amount );
			$ReturnPath = add_query_arg( 'gateway', $query, $subscription_data['return_url'] );

			$merchant_id  = $rcp_options['_123pay_merchant_id'];
			$amount       = $Price;
			$callback_url = $ReturnPath;

			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, 'https://123pay.ir/api/v1/create/payment' );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, "merchant_id=$merchant_id&amount=$amount&callback_url=$callback_url" );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			$response = curl_exec( $ch );
			curl_close( $ch );
			$result = json_decode( $response );

			if ( $result->status ) {
				ob_end_flush();
				ob_end_clean();
				if ( ! headers_sent() ) {
					header( 'Location: ' . $result->payment_url );
					exit;
				} else {
					echo "<script type='text/javascript'>window.onload = function () { top.location.href = '" . $result->payment_url . "'; };</script>";
					exit;
				}
			} else {
				wp_die( sprintf( __( 'متاسفانه پرداخت به دلیل خطای زیر امکان پذیر نمی باشد . <br/><b> %s </b>', 'rcp__123pay' ), $this->Fault( $result->message ) ) );
			}
			exit();
		}

		public function _123pay_Verify() {
			if ( ! isset( $_GET['gateway'] ) ) {
				return;
			}

			if ( ! class_exists( 'RCP_Payments' ) ) {
				return;
			}

			global $rcp_options, $wpdb, $rcp_payments_db_name;
			@session_start();
			$__123pay_session = _123PAY_Session::get_instance();
			if ( isset( $__123pay_session['_123pay_payment_data'] ) ) {
				$_123pay_payment_data = $__123pay_session['_123pay_payment_data'];
			} else {
				$_123pay_payment_data = isset( $_SESSION["_123pay_payment_data"] ) ? $_SESSION["_123pay_payment_data"] : '';
			}

			$query = isset( $rcp_options['_123pay_query_name'] ) ? $rcp_options['_123pay_query_name'] : '_123pay';

			if ( ( $_GET['gateway'] == $query ) && $_123pay_payment_data ) {
				$user_id           = $_123pay_payment_data['user_id'];
				$subscription_name = $_123pay_payment_data['subscription_name'];
				$subscription_key  = $_123pay_payment_data['subscription_key'];
				$amount            = $_123pay_payment_data['amount'];

				$subscription_id = rcp_get_subscription_id( $user_id );
				$user_data       = get_userdata( $user_id );
				$payment_method  = isset( $rcp_options['_123pay_name'] ) ? $rcp_options['_123pay_name'] : __( 'سامانه پرداخت یک دو سه پی', 'rcp__123pay' );

				if ( ! $user_data || ! $subscription_id || ! rcp_get_subscription_details( $subscription_id ) ) {
					return;
				}

				$new_payment = 1;
				if ( $wpdb->get_results( $wpdb->prepare( "SELECT id FROM " . $rcp_payments_db_name . " WHERE `subscription_key`='%s' AND `payment_type`='%s';", $subscription_key, $payment_method ) ) ) {
					$new_payment = 0;
				}

				unset( $GLOBALS['_123pay_new'] );
				$GLOBALS['_123pay_new'] = $new_payment;
				global $new;
				$new = $new_payment;

				if ( $new_payment == 1 ) {
					$merchant_id = $rcp_options['_123pay_merchant_id'];
					$Price       = intval( $amount );
					if ( $rcp_options['currency'] == 'تومان' || $rcp_options['currency'] == 'TOMAN' || $rcp_options['currency'] == 'تومان ایران' || $rcp_options['currency'] == 'Iranian Toman (&#65020;)' ) {
						$Price = $Price * 10;
					}

					$State  = $_REQUEST['State'];
					$RefNum = $_REQUEST['RefNum'];
					if ( $State == 'OK' ) {
						$ch = curl_init();
						curl_setopt( $ch, CURLOPT_URL, 'https://123pay.ir/api/v1/verify/payment' );
						curl_setopt( $ch, CURLOPT_POSTFIELDS, "merchant_id=$merchant_id&RefNum=$RefNum" );
						curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
						curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
						$response = curl_exec( $ch );
						curl_close( $ch );
						$result = json_decode( $response );

						if ( $result->status ) {
							$payment_status = 'completed';
							$fault          = 0;
							$transaction_id = $RefNum;
						} else {
							$payment_status = 'failed';
							$fault          = $result->message;
							$transaction_id = 0;
						}
					} else {
						$payment_status = 'cancelled';
						$fault          = 0;
						$transaction_id = 0;
					}

					unset( $GLOBALS['_123pay_payment_status'] );
					unset( $GLOBALS['_123pay_transaction_id'] );
					unset( $GLOBALS['_123pay_fault'] );
					unset( $GLOBALS['_123pay_subscription_key'] );
					$GLOBALS['_123pay_payment_status']   = $payment_status;
					$GLOBALS['_123pay_transaction_id']   = $transaction_id;
					$GLOBALS['_123pay_subscription_key'] = $subscription_key;
					$GLOBALS['_123pay_fault']            = $fault;
					global $_123pay_transaction;
					$_123pay_transaction                             = array();
					$_123pay_transaction['_123pay_payment_status']   = $payment_status;
					$_123pay_transaction['_123pay_transaction_id']   = $transaction_id;
					$_123pay_transaction['_123pay_subscription_key'] = $subscription_key;
					$_123pay_transaction['_123pay_fault']            = $fault;

					if ( $payment_status == 'completed' ) {
						$payment_data = array(
							'date'             => date( 'Y-m-d g:i:s' ),
							'subscription'     => $subscription_name,
							'payment_type'     => $payment_method,
							'subscription_key' => $subscription_key,
							'amount'           => $amount,
							'user_id'          => $user_id,
							'transaction_id'   => $transaction_id
						);

						do_action( 'RCP__123pay_Insert_Payment', $payment_data, $user_id );

						$rcp_payments = new RCP_Payments();
						$rcp_payments->insert( $payment_data );

						rcp_set_status( $user_id, 'active' );

						if ( version_compare( RCP_PLUGIN_VERSION, '2.1.0', '<' ) ) {
							rcp_email_subscription_status( $user_id, 'active' );
							if ( ! isset( $rcp_options['disable_new_user_notices'] ) ) {
								wp_new_user_notification( $user_id );
							}
						}

						update_user_meta( $user_id, 'rcp_payment_profile_id', $user_id );
						update_user_meta( $user_id, 'rcp_signup_method', 'live' );
						update_user_meta( $user_id, 'rcp_recurring', 'no' );

						$subscription          = rcp_get_subscription_details( rcp_get_subscription_id( $user_id ) );
						$member_new_expiration = date( 'Y-m-d H:i:s', strtotime( '+' . $subscription->duration . ' ' . $subscription->duration_unit . ' 23:59:59' ) );
						rcp_set_expiration_date( $user_id, $member_new_expiration );
						delete_user_meta( $user_id, '_rcp_expired_email_sent' );

						$log_data = array(
							'post_title'   => __( 'تایید پرداخت', 'rcp__123pay' ),
							'post_content' => __( 'پرداخت با موفقیت انجام شد . کد تراکنش : ', 'rcp__123pay' ) . $transaction_id . __( ' .  روش پرداخت : ', 'rcp__123pay' ) . $payment_method,
							'post_parent'  => 0,
							'log_type'     => 'gateway_error'
						);

						$log_meta = array(
							'user_subscription' => $subscription_name,
							'user_id'           => $user_id
						);

						$log_entry = WP_Logging::insert_log( $log_data, $log_meta );

						do_action( 'RCP__123pay_Completed', $user_id );
					}

					if ( $payment_status == 'cancelled' ) {
						$log_data = array(
							'post_title'   => __( 'انصراف از پرداخت', 'rcp__123pay' ),
							'post_content' => __( 'تراکنش به دلیل انصراف کاربر از پرداخت ، ناتمام باقی ماند .', 'rcp__123pay' ) . __( ' روش پرداخت : ', 'rcp__123pay' ) . $payment_method,
							'post_parent'  => 0,
							'log_type'     => 'gateway_error'
						);

						$log_meta = array(
							'user_subscription' => $subscription_name,
							'user_id'           => $user_id
						);

						$log_entry = WP_Logging::insert_log( $log_data, $log_meta );

						//Action For _123pay or RCP Developers...
						do_action( 'RCP__123pay_Cancelled', $user_id );
					}

					if ( $payment_status == 'failed' ) {
						$log_data = array(
							'post_title'   => __( 'خطا در پرداخت', 'rcp__123pay' ),
							'post_content' => __( 'تراکنش به دلیل خطای رو به رو ناموفق باقی ماند :', 'rcp__123pay' ) . $this->Fault( $fault ) . __( ' روش پرداخت : ', 'rcp__123pay' ) . $payment_method,
							'post_parent'  => 0,
							'log_type'     => 'gateway_error'
						);

						$log_meta = array(
							'user_subscription' => $subscription_name,
							'user_id'           => $user_id
						);

						$log_entry = WP_Logging::insert_log( $log_data, $log_meta );

						do_action( 'RCP__123pay_Failed', $user_id );
					}
				}
				add_filter( 'the_content', array( $this, '_123pay_Content_After_Return' ) );
			}
		}


		public function _123pay_Content_After_Return( $content ) {
			global $_123pay_transaction, $new;

			$__123pay_session = _123PAY_Session::get_instance();
			@session_start();

			$new_payment = isset( $GLOBALS['_123pay_new'] ) ? $GLOBALS['_123pay_new'] : $new;

			$payment_status = isset( $GLOBALS['_123pay_payment_status'] ) ? $GLOBALS['_123pay_payment_status'] : $_123pay_transaction['_123pay_payment_status'];
			$transaction_id = isset( $GLOBALS['_123pay_transaction_id'] ) ? $GLOBALS['_123pay_transaction_id'] : $_123pay_transaction['_123pay_transaction_id'];
			$fault          = isset( $GLOBALS['_123pay_fault'] ) ? $this->Fault( $GLOBALS['_123pay_fault'] ) : $this->Fault( $_123pay_transaction['_123pay_fault'] );

			if ( $new_payment == 1 ) {
				$_123pay_data = array(
					'payment_status' => $payment_status,
					'transaction_id' => $transaction_id,
					'fault'          => $fault
				);

				$__123pay_session['_123pay_data'] = $_123pay_data;
				$_SESSION["_123pay_data"]         = $_123pay_data;
			} else {
				if ( isset( $__123pay_session['_123pay_data'] ) ) {
					$_123pay_payment_data = $__123pay_session['_123pay_data'];
				} else {
					$_123pay_payment_data = isset( $_SESSION["_123pay_data"] ) ? $_SESSION["_123pay_data"] : '';
				}

				$payment_status = isset( $_123pay_payment_data['payment_status'] ) ? $_123pay_payment_data['payment_status'] : '';
				$transaction_id = isset( $_123pay_payment_data['transaction_id'] ) ? $_123pay_payment_data['transaction_id'] : '';
				$fault          = isset( $_123pay_payment_data['fault'] ) ? $this->Fault( $_123pay_payment_data['fault'] ) : '';
			}

			$message = '';

			if ( $payment_status == 'completed' ) {
				$message = '<br/>' . __( 'پرداخت با موفقیت انجام شد . کد تراکنش : ', 'rcp__123pay' ) . $transaction_id . '<br/>';
			}

			if ( $payment_status == 'cancelled' ) {
				$message = '<br/>' . __( 'تراکنش به دلیل انصراف شما نا تمام باقی ماند .', 'rcp__123pay' );
			}

			if ( $payment_status == 'failed' ) {
				$message = '<br/>' . __( 'تراکنش به دلیل خطای زیر ناموفق باقی ماند :', 'rcp__123pay' ) . '<br/>' . $fault . '<br/>';
			}

			return $content . $message;
		}

		private static function Fault( $error ) {
			return $error;
		}
	}
}
new RCP__123pay();
if ( ! function_exists( 'change_cancelled_to_pending_By_123PAY' ) ) {
	add_action( 'rcp_set_status', 'change_cancelled_to_pending_By_123PAY', 10, 2 );
	function change_cancelled_to_pending_By_123PAY( $status, $user_id ) {
		if ( 'cancelled' == $status ) {
			rcp_set_status( $user_id, 'expired' );
		}

		return true;
	}
}
?>