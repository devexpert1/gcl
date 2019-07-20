<script src="https://www.paypalobjects.com/api/checkout.js"></script>
<script>

    paypal.Button.render({
        @if(\Options::getoptionmatch3('paypal_mode')=='0')
        env: 'sandbox', // sandbox | production
        @else
        env: 'production',
        @endif

        // PayPal Client IDs - replace with your own
        // Create a PayPal app: https://developer.paypal.com/developer/applications/create
        client: {
            @if(\Options::getoptionmatch3('paypal_mode')=='0')
            sandbox: "{{ \Options::getoptionmatch1('paypal_sandbox') }}",
            @else
            production: "{{ \Options::getoptionmatch1('paypal_live') }}",
            @endif
        },

        style: {
            layout: 'vertical',  // horizontal | vertical
            size:   'responsive',    // medium | large | responsive
            shape:  'rect',      // pill | rect
            color:  'blue'       // gold | blue | silver | black
        },

        // Show the buyer a 'Pay Now' button in the checkout flow
        commit: true,

        // payment() is called when the button is clicked
        payment: function(data, actions) {
            /*$("form[name='packages']").submit();
            if ( $("form[name='packages']").valid() ){
                alert('valid');
            } else {
                alert('Not valid');
                return;
            }*/

            // Make a call to the REST api to create the payment
            return actions.payment.create({
                payment: {
                    transactions: [
                        {
                            amount: { total: {{ $subscription->price }}, currency: 'USD' }
                            // amount: { total: 1, currency: 'USD' }
                        }
                    ]
                }
            });
        },

        // onAuthorize() is called when the buyer approves the payment
        onAuthorize: function(data, actions) {

            // Make a call to the REST api to execute the payment
            return actions.payment.execute().then(function(data) {
                // window.alert('Payment Complete!');

                var transaction_id = data.transactions[0].related_resources[0].sale.id;
                var status = data.transactions[0].related_resources[0].sale.state;
	            var amount = data.transactions[0].amount["total"];
	            var currency = data.transactions[0].amount["currency"];

                var name = $("#name").val();
                var email = $("#eemail").val();
                var password = $("#password").val();
                
                $.ajax({
                   type: "POST",
                   url: "{{ url('packages/'.$subscription->id) }}",
                   data:{
                            "_token": "{{ csrf_token() }}",
                            name:name, 
                            email:email, 
                            password:password, 
                            transaction_id:transaction_id, 
                            status:status, 
                            amount:amount, 
                            currency:currency
                        },
                   success: function(data)
                   {
                       // if(data == 1){
                            $("#success").css("display", "");
                            $("#success_msg").text("Payment done successfully.");
                            setTimeout( function(){ 
                                $("#success").css("display", "none");

                                location = "{{ route('dashboard', ['type'=>'super-admin']) }}";
                            }, 3000);
                       // }
                   }
                });
            });
        },

        onCancel: function (data, actions) {
            $("#danger").css("display", "");
            $("#danger_msg").text("Payment was cancelled.");
            setTimeout( function(){ 
                $("#danger").css("display", "none");
            }, 3000);
        },

        onError: function (err) {
            $("#danger").css("display", "");
            $("#danger_msg").text(err);
            setTimeout( function(){ 
                $("#danger").css("display", "none");
            }, 3000);
        }

    }, '#paypal-button-container');

</script>