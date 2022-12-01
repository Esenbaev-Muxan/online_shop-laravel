<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\Product;
use App\Models\Card;

use App\Models\Order;

use Session;
use Stripe;

class HomeController extends Controller
{
    //

    public function index()
    {
        $product=Product::paginate(10);
        return view('home.userpage', compact('product'));
    }

    public function redirect(){
        $usertype = Auth::user()->usertype;
        $product=Product::paginate(10);

        if($usertype=='1')
        {
            return view('admin.home');
        }
        else 
        {
            return view('home.userpage', compact('product'));
        }

    }

    public function product_details($id)
    {
        
        $product=product::find($id);

        return view('home.product_details',compact('product'));
    }

    public function add_card(Request $request, $id)
    {

        if(Auth::id())
        {
            
            $user=Auth::user();
            $product=product::find($id);
        
            $card=new card;
            
            $card->name=$user->name;
            $card->email=$user->email;
            $card->phone=$user->phone;
            $card->address=$user->address;
            $card->user_id=Auth::user()->id;;


            $card->Product_title=$product->title;

            if($product->discount_price!=null)
            {
                $card->price=$product->discount_price * $request->quantity;
            }
            else 
            {
                $card->price=$product->price * $request->quantity;
            }

            $card->price=$product->price;

            $card->image=$product->image;

            $card->Product_id=$product->id;
            $card->quantity=$request->quantity;

            $card->save();

            return redirect()->back();
            
        }

        else 
        {
            return redirect('login');
        }
    }

    public function show_cart()
    {
        if(Auth::id())
        {
            $id=Auth::user()->id;
            $card=card::where('user_id', '=', $id)->get();
            return view('home.showcart',compact('card'));
        }
        else 
        {
            return redirect('login');
        }

        
    }

    public function remove_cart($id)
    {

        $card=card::find($id);

        $card->delete();

        return redirect()->back();
    }

    public function cash_order()
    {
        $user=Auth::user();

        $userid=$user->id;

        $data=card::where('user_id', '=', $userid)->get();

        foreach($data as $data)
        {

            $order=new order;

            $order->name=$data->name;

            $order->email=$data->email;

            $order->phone=$data->phone;

            $order->address=$data->address;

            $order->address=$data->address;

            $order->user_id=$data->user_id;

            $order->product_title=$data->product_title;

            $order->price=$data->price;

            $order->quantity=$data->quantity;

            $order->image=$data->image;

            $order->product_id=$data->Product_id;


            $order->payment_status='cash on delivery';

            $order->delivery_status='processing';

            $order->save();

            $card_id=$data->id;
            $card=card::find($card_id);

            $card->delete();

        }
        return redirect()->back()->with('message', 'We have Received your Order. We will connect with you soon...');
    }

    public function stripe($totalprice)
    {

        return view('home.stripe',compact('totalprice'));
    }


    public function stripePost(Request $request, $totalprice)
    {
   
        Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
    
        Stripe\Charge::create ([
                "amount" => $totalprice * 100,
                "currency" => "usd",
                "source" => $request->stripeToken,
                "description" => "Thanks for payment" 
        ]);

        $user=Auth::user();

        $userid=$user->id;

        $data=card::where('user_id', '=', $userid)->get();

        foreach($data as $data)
        {

            $order=new order;

            $order->name=$data->name;

            $order->email=$data->email;

            $order->phone=$data->phone;

            $order->address=$data->address;

            $order->address=$data->address;

            $order->user_id=$data->user_id;

            $order->product_title=$data->product_title;

            $order->price=$data->price;

            $order->quantity=$data->quantity;

            $order->image=$data->image;

            $order->product_id=$data->Product_id;


            $order->payment_status='Paid';

            $order->delivery_status='processing';

            $order->save();

            $card_id=$data->id;
            $card=card::find($card_id);

            $card->delete();

        }
      
        Session::flash('success', 'Payment successful!');
              
        return back();
    }

}
