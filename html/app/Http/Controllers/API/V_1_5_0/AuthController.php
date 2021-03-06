<?php

namespace App\Http\Controllers\API\V_1_5_0;

use Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Models\PromotorModel;
use App\Http\Models\PromotorMetaModel;
use App\Http\Models\TokenModel;

class AuthController extends Controller
{
    /**
     * Promotor model container
     *
     * @access protected
     */
    protected $promotor;

    /**
     * Promotor_meta model container
     *
     * @access protected
     */
    protected $promotor_meta;
    
    /**
     * Token model container
     *
     * @access protected
     */
    protected $token;

    /**
     * Object constructor
     *
     * @access public
     * @return Void
     */
    public function __construct()
    {
        $this->promotor = new PromotorModel();
        $this->promotor_meta = new PromotorMetaModel();
        $this->token    = new TokenModel();
    }
    
    /**
     * Check current user authentication status
     *
     * @access public
     * @param Request $request
     * @return Response
     */
    public function check(Request $request)
    {
        $ID     = 0;
        $token  = $request->get('token', false);
        
        if (!$token)
        {
            return response()->json(['error' => 'no-token']);
        }

        // Validate user
        $promotorID = $this->token->decode($token);
        $promotor   = $this->promotor->getOne($promotorID);
        
        if(!$promotor)
        {
            return response()->json(['ID' => 0]);
        }
        
        $ID = $promotor->ID;

        //check user has block
        $promotor_meta = $this->promotor_meta->get($promotor->ID, 'block');

        if($promotor_meta)
        {
            $ID = 0;
        }
        
        return response()->json(['ID' => $ID]);
    }

    /**
     * Handle login POST request
     *
     * @access public
     * @param Request
     * @return Response
     */
    public function login(Request $request)
    {
        // Validate parameter
        $phone      = $request->get('phone', false);
        $password   = $request->get('password', false);
        
        if (!($phone && $password))
        {
            return response()->json(['error' => 'login-error']);
        }
        
        // Convert phone number
        if (substr($phone, 0, 1) === '0')
        {
            $phone = preg_replace('/^0/', '+62', $phone);
        }
        
        // Get promotor
        $promotor = $this->promotor->getByPhone($phone);

        if (!$promotor) // If user not found show error
        {
            return response()->json(['error' => 'login-error']);
        }
        
        $user_token = true;
        
        // Check user default
        if($promotor->password === 'havas')
        {
            if ($password !== 'havas')
            {
                return response()->json(['error' => 'login-error']); 
            }
            
            $user_token = false;
        }
        else
        {
            // Check hash
            $hashValid = Hash::check($password, $promotor->password);
            
            if (!$hashValid)
            {
                return response()->json(['error' => 'login-error', 'msg' => 'hash']);
            }
        }

        //check user has block
        $promotor_meta = $this->promotor_meta->get($promotor->ID, 'block');

        if($promotor_meta)
        {
            return response()->json(['error' => 'login-error']);
        }

        $ID = $promotor->ID;
        
        //create tokenon
        if($ID < 10 )
        {
            $ID = '0'.''.$promotor->ID;
        }
        
        $token = $ID.''.str_random(4);
        
        $type = $promotor->type;
        
        // Quickfix branch manager access
        if ($type === 'branch-manager')
        {
            $type = 'tl';
        }

        // Give token response
        return response()->json([
            'token'         => $this->token->encode($promotor->ID, $token),
            'user_type'     => $type,
            'user_token'    => $user_token
        ]);
    }
    
}
