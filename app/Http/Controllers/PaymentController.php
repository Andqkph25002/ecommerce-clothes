<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class PaymentController extends Controller
{
    public function Payment(Request $request)
    {
        $data = array();
        $data['name'] = $request->name;
        $data['phone'] = $request->phone;
        $data['email'] = $request->email;
        $data['address'] = $request->address;
        $data['city'] = $request->city;
        $data['payment'] = $request->payment;
        if ($request->payment == 'stripe') {
            return view('pages.payment.stripe', compact('data'));
        } else if ($request->payment == 'paypal') {
            echo "Thanh toán Paypal";
        } else if ($request->payment == 'oncash') {
            return view('pages.payment.oncash', compact('data'));
        } else {
            echo "Cash On Delivery";
        }
    }
    public function StripeCharge(Request $request)
    {
        $user = Auth::id();
        $total = $request->total;
        $email = Auth::user()->email;

        // Set your secret key. Remember to switch to your live secret key in production.
        // See your keys here: https://dashboard.stripe.com/apikeys
        \Stripe\Stripe::setApiKey('sk_test_51MyzCKFqqa10ptdguOEAr7lkiqQ1yeh8LuYwTPfdE5GeiZS47iuYw3swa9RcaoEZQveIf6yrgED99VhHok2wY02n000e7XWmVJ');

        // Token is created using Checkout or Elements!
        // Get the payment token ID submitted by the form:
        $token = $_POST['stripeToken'];

        $charge = \Stripe\Charge::create([
            'amount' => 999 * 100,
            'currency' => 'usd',
            'description' => 'Man Shop',
            'source' => $token,
            'metadata' => ['order_id' => uniqid()],
        ]);
        $data = array();
        $data['user_id'] = Auth::id();
        $data['payment_id'] = $charge->payment_method;
        $data['paying_amount'] = $charge->amount;
        $data['blnc_transection'] = $charge->balance_transaction;
        $data['stripe_order_id'] = $charge->metadata->order_id;
        $data['shipping'] = $request->shipping;
        $data['vat'] = $request->vat;
        $data['total'] = $request->total;
        $data['payment_type'] = $request->payment_type;
        $data['status_code'] = mt_rand(100000, 999999);
        if (Session::has('coupon')) {
            $data['subtotal'] = Session::get('coupon')['balance'];
        } else {
            $data['subtotal'] = Cart::subtotal();
        }
        $data['status'] = 0;
        $data['date'] = date('d-m-y');
        $data['month'] = date('F');
        $data['year'] = date('Y');
        $order_id = DB::table('orders')->insertGetId($data);


        Mail::to($email)->send(new InvoiceMail($data));

        $shipping = array();
        $shipping['order_id'] = $order_id;
        $shipping['ship_name'] = $request->ship_name;
        $shipping['ship_phone'] = $request->ship_phone;
        $shipping['ship_email'] = $request->ship_email;
        $shipping['ship_address'] = $request->ship_address;
        $shipping['ship_city'] = $request->ship_city;
        DB::table('shipping')->insert($shipping);


        $content = Cart::content();
        $details = array();
        foreach ($content as $row) {
            $details['order_id'] = $order_id;
            $details['product_id'] = $row->id;
            $details['product_name'] = $row->name;
            $details['color'] = $row->options->color;
            $details['size'] = $row->options->size;
            $details['quantity'] = $row->qty;
            $details['singleprice'] = $row->price;
            $details['totalprice'] = $row->qty * $row->price;
            DB::table('orders_details')->insert($details);
        }

        Cart::destroy();
        if (Session::has('coupon')) {
            Session::forget('coupon');
        }
        $notification = array(
            'messege' => 'Order Process Successfully Done',
            'alert-type' => 'success'
        );
        return Redirect()->to('/')->with($notification);
    }
    public function OnCash(Request $request){
         
      
   
        $data = array();
        $data['user_id'] = Auth::id();
        $data['shipping'] = $request->shipping;
        $data['vat'] = $request->vat;
        $data['total'] = $request->total;
        $data['payment_type'] = $request->payment_type;
        $data['status_code'] = mt_rand(100000,999999);
    
        if (Session::has('coupon')) {
          $data['subtotal'] = Session::get('coupon')['balance'];
        }else{
          $data['subtotal'] = Cart::Subtotal();
        }
        $data['status'] = 0;
        $data['date'] = date('d-m-y');
        $data['month'] = date('F');
        $data['year'] = date('Y');
        $order_id = DB::table('orders')->insertGetId($data);
    
       
        /// Insert Shipping Table 
    
        $shipping = array();
        $shipping['order_id'] = $order_id;
        $shipping['ship_name'] = $request->ship_name;
        $shipping['ship_phone'] = $request->ship_phone;
        $shipping['ship_email'] = $request->ship_email;
        $shipping['ship_address'] = $request->ship_address;
        $shipping['ship_city'] = $request->ship_city;
        DB::table('shipping')->insert($shipping);
    
        // Insert Order Details Table
        
        $content = Cart::content();
        $details = array();
        foreach ($content as $row) {
        $details['order_id'] = $order_id;
        $details['product_id'] = $row->id;
        $details['product_name'] = $row->name;
        $details['color'] = $row->options->color;
        $details['size'] = $row->options->size;
        $details['quantity'] = $row->qty;
        $details['singleprice'] = $row->price;
        $details['totalprice'] = $row->qty*$row->price;
        DB::table('orders_details')->insert($details); 
    
        }
    
        Cart::destroy();
        if (Session::has('coupon')) {
          Session::forget('coupon');
        }
        $notification=array(
                            'messege'=>'Order Process Successfully Done',
                            'alert-type'=>'success'
                             );
                           return Redirect()->to('/')->with($notification);
      
      }

    public function SuccessList()
    {

        $order = DB::table('orders')->where('user_id', Auth::id())->where('status', 3)->orderBy('id', 'DESC')->limit(5)->get();

        return view('pages.returnorder', compact('order'));
    }
    public function RequestReturn($id)
    {
        DB::table('orders')->where('id', $id)->update(['return_order' => 1]);
        $notification = array(
            'messege' => 'Order Request Done',
            'alert-type' => 'success'
        );
        return Redirect()->back()->with($notification);
    }
}
