<?php

	/**
	 * @package todo
	 * @subpackage controller
	 */
	class AdminController extends Controller
	{
		function __construct()
		{
            if( $_SERVER[ 'REQUEST_URI' ] != '/Admin/Login' )
			 AdminController::EnsureAdmin();

			parent::__construct();
		}

		function Index()
		{
			echo $this->Decorate( 'admin/index.tpl' );
		}
		
		function Login()
		{
			if( $_SERVER[ 'REQUEST_METHOD' ] == 'POST' )
			{
				$input = Common::Inputs( array( 'username', 'password' ), INPUT_POST );
				$admin = Admin::Login( $input->username, $input->password );

				if( $admin )
				{
					$_SESSION['admin'] = $admin;
                    $_SESSION[ 'user_notification' ][] = array( 'text' => "Admin {$admin->username} logged in.", 'type' => 'notice' );
					header( "Location: /" );
					exit;
				}
				else
				{
					$_SESSION[ 'user_notification' ][] = array( 'text' => "Login failed.", 'type' => 'error' );
				}
			}

			$this->assign( 'breadcrumbs', array( array( 'link' => '/Admin/Login', 'name' => 'Admin Login' ) ) );
			echo $this->Decorate( 'admin/login.tpl' );
		}

		function Logout()
		{
			$_SESSION[ 'user_notification' ][] = array( 'type' => 'notify', 'text' => "Admin logged out." );
			unset( $_SESSION['admin'] );
			header( "Location: /" );
			exit;
		}

        function ListAll()
        {
            if( !$_SESSION[ 'admin' ] )
                self::Redirect( '/' );

            $this->assign( 'admins', Admin::VendorCollection( $_SESSION[ 'admin' ]->vendor ) );
            echo $this->Decorate( 'admin/admin/list.tpl' );
        }

		function Edit()
		{
			if( $_SERVER['REQUEST_METHOD'] == 'POST' )
			{
				$input = Common::Inputs( array( 'username', 'password' ), INPUT_POST );
				if( strlen( $input->username ) > 2 && strlen( $input->password ) > 5 )
				{
					$admin = new Admin();
					$admin->username = $input->username;
					$admin->password = md5( $input->password );
					$admin->Save();
					$_SESSION[ 'user_notification' ][] = array( 'type' => 'notice', 'text' => 'Administrator created.' );
				}
				elseif( strlen( $input->username ) < 3 )
					$_SESSION[ 'user_notification' ][] = array( 'type' => 'error', 'text' => 'Administrator username too short, should be at least 3 characters.' );
				elseif( strlen( $input->password ) < 5 )
					$_SESSION[ 'user_notification' ][] = array( 'type' => 'error', 'text' => 'Administrator password too short, should be at least 5 characters.' );
			}

			self::Redirect( '/Admin/ListAll/' );
		}

		function Delete( $id )
		{
			$admin = Admin::Retrieve( $id );
			if( $admin->id == $_SESSION[ 'admin' ]->id )
			{
				$_SESSION[ 'user_notification' ][] = array( 'type' => 'error', 'text' => 'You cannot delete yourself.' );
			}
			elseif( $admin )
			{
				$admin->Delete();
			}
			else
				$_SESSION[ 'user_notification' ][] = array( 'type' => 'error', 'text' => 'No such admin.' );

			self::Redirect( '/Admin/ListAll/' );

		}

		function ChangePassword( $id )
		{
			$admin = Admin::Retrieve( $id );

			if( $_SERVER['REQUEST_METHOD'] == 'POST' )
			{
				$input = Common::Inputs( array( 'password', 'confirm_password' ), INPUT_POST );

				if( $input->password != $input->confirm_password )
				{
					$_SESSION[ 'user_notification' ][] = array( 'type' => 'error', 'text' => 'Password and confirmation does not match, password not changed.' );
				}
				elseif( strlen( $input->password ) < 5 )
				{
					$_SESSION[ 'user_notification' ][] = array( 'type' => 'error', 'text' => 'Password too short, password must contain at least 5 characters.' );
				}
				else
				{
					if( !$admin )
					{
						$_SESSION['user_notification'][] = array( 'type' => 'error', 'text' => 'No such admin.' );
					}
					else
					{
						$admin->password = md5( $input->password );
						$admin->Save();
						$_SESSION[ 'user_notification' ][] = array( 'type' => 'notice', 'text' => 'Password changed.' );
					}

					self::Redirect( '/Admin/ListAll/' );
				}
			}

			$this->assign( 'admin', $admin );
			echo $this->Decorate( 'admin/admin/change_password.tpl' );
		}

        static function EnsureAdminAndProductOwner( $id )
        {
        }

        static function EnsureAdmin()
        {
            if( !$_SESSION[ 'admin' ] )
			{
				if( !PRODUCTION )
					$_SESSION[ 'user_notification' ][] = array( 'type' => 'error', 'text' => 'Admin - please log in' );

				self::Redirect( '/Error/NotFound' );
			}
        }
	}
