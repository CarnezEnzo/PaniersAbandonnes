{declare_assets directory='assets'}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    
    <link href="https://fonts.googleapis.com/css?family=Josefin+Sans:300,400,600" rel="stylesheet">
    
    <style type="text/css">
        {literal}
        body {
            margin: 0;
            padding: 0;
            background-color: #e5e3e3;
            font-family:'Josefin Sans', sans-serif;
        }
    
        table.main {
            width: 960px;
            margin: 0 auto;
        }
        
        td {
            padding: 10px 0;
        }
        
        p {
            margin-top: 10px;
            text-align: center;
            margin-bottom: 10px;
        }
        
        p.left {
            text-align: left;
        }
        
        table.wrapper {
            width: 100%;
        }
        
        td.separator {
            padding: 0;
        }
        
        td.separator p {
            background-color: #f9f5f4;
            height: 45px;
            margin: 0;
        }
        
        td.head p {
            background-color: #3f3a38;
            height: 50px;
            margin: 0;
        }
        
        td.head-bottom p {
            height: 220px;
        }

        td.content {
            padding-right: 20px;
            padding-bottom: 30px;
        }

        td.sidebar {
            color: #6d5f5a;
            background-color: #f9f5f4;
            vertical-align: top;
            padding-left: 10px;
            padding-right: 10px;
            white-space: nowrap;
        }
        
        td.footer {
            color: #6d5f5a;
            background-color: #f9f5f4;
            padding: 20px 0;
        }

        td.sidebar hr {
            width: 30%;
        }
        
        .title {
            font-weight: bold;
            font-size: 140%;
        }

        .sidebar-subtitle {
            font-size: 90%;
        }

        .subtitle {
            font-weight: 600;
            color: #6d5f5a;
        }
        
        .wrapper-panier {
            background-color: #fff;
            padding: 10px;
            width: 80%;
            margin: 0 auto 40px;
        }
        
        table.panier {
            border-spacing: 0;
            border-collapse : collapse;
            width: 100%;
        }
        
        table.panier td {
            color: #6d5f5a;
            vertical-align: top;
            padding: 10px;
        }

        table.panier td.head {
            font-weight: 600;
            border-bottom: 1px solid #6d5f5a;
        }

        table.panier td.foot {
            font-weight: 600;
            border-top: 1px solid #6d5f5a;
            border-bottom: 1px solid #a79893;
        }

        table.panier td p {
            margin-top: 0;
        }
        
        table.panier td small {
            font-size: 75%;
        }

        .product-attributes, .product-attributes li {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        
        .btn-panier {
            color: #fff;
            background-color: #f15a24;
            padding: 10px 20px;
            text-transform: uppercase;
            text-decoration: none;
        }
        
        {/literal}
    </style>
</head>
<body>
    <table width="960" class="main">
        <tr>
            <td class="head head-top"><p>&nbsp;</p></td>
        </tr>
        
        <tr>
            <td>
                <p><a href="{navigate to="index"}"><img alt="Jacques & Déméter" src="{image file='assets/img/logo-menu.png' source='PaniersAbandonnes'}"></a></p>
            </td>
        </tr>
        
        <tr>
            <td class="separator"><p>&nbsp;</p></td>
        </tr>
        
        <tr>
            <td>
                <table width="100%" class="wrapper">
                    <tr>
                        {* mail content *}
                        <td class="content">
                            {block name="contenu"}{/block}
                            
                            <div class="wrapper-panier">
                                <table width="100%" class="panier">
                                <tr>
                                    <td class="head">Article</td>
                                    <td class="head">Nom du produit</td>
                                    <td class="head" style="text-align: right;">Prix</td>
                                    <td class="head" style="text-align: center;">Quantité</td>
                                    <td class="head">&nbsp;</td>
                                </tr>
                                {loop type="panierabandonne.cartitem" name="pa" cart_id=$cart_id}
                                    <tr>
                                        <td>
                                            {loop type="image" name="pi" source="product" source_id=$PRODUCT_ID width="118" height="85" limit="1" force_return="true"}
                                                <img src="{$IMAGE_URL}">
                                            {/loop}
                                        </td>
                                        
                                        <td>
                                            <p class="left" style="text-transform: uppercase; margin-bottom: 5px;">{$TITLE nofilter}</p>
                                            
                                            <ul class="product-attributes">
                                                {loop type="attribute_combination" name="product_options" product_sale_elements=$PRODUCT_SALE_ELEMENTS_ID order="manual"}
                                                {$title = ($ATTRIBUTE_CHAPO) ? $ATTRIBUTE_CHAPO : $ATTRIBUTE_TITLE}
                                                    <li>{$title}: {$ATTRIBUTE_AVAILABILITY_TITLE}</li>
                                                {/loop}
                                            </ul>
                                        </td>
                                        
                                        <td nowrap="nowrap" style="white-space: nowrap; text-align: right;">
                                            {if $IS_PROMO == 1}
                                                {$unit_price = $PROMO_TAXED_PRICE}
                                                {$unit_price_ht = $PROMO_PRICE}
        
        
                                                <span class="normal-price">{format_money number=$PROMO_TAXED_PRICE}</span>
                                                <br>
                                                <small>{intl l="%price HT" price={format_money number=$PROMO_PRICE}}</small>
                                            {else}
                                                {$unit_price = $TAXED_PRICE}
                                                {$unit_price_ht = $PRICE}
        
                                                {format_money number=$TAXED_PRICE}
                                                <br>
                                                <small>{intl l="%price HT" price={format_money number=$PRICE}}</small>
                                            {/if}
    
                                            {$total_product_price = $total_product_price + $QUANTITY * $unit_price}
                                            {$total_product_price_ht = $total_product_price_ht + $QUANTITY * $unit_price_ht}
                                        </td>
                                        
                                        <td style="text-align: center;">{$QUANTITY}</td>
                                        
                                        <td  nowrap="nowrap" style="white-space: nowrap;text-align: right;">
                                            {format_money number={$QUANTITY * $unit_price}}
                                            <br>
                                            <small>{intl l="%price HT" price={format_money number={$QUANTITY * $unit_price_ht}}}</small>
                                        </td>
                                    </tr>
                                {/loop}
                                <tr>
                                    <td colspan="4" class="foot" style="text-align: right">
                                        TOTAL PRODUITS TTC
                                    </td>
                                    <td nowrap="nowrap" class="foot" style="white-space: nowrap; text-align: right;">
                                        {format_money number=$total_product_price}
                                        <br>
                                        <small>{intl l="%price HT" price={format_money number=$total_product_price_ht}}</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="999">
                                        <br>&nbsp;<br>
                                        <p><a class="btn-panier" href="{url path="/back-to-cart/%token" token=$login_token}">RETOURNER À MON PANIER</a></p>
                                    </td>
                                </tr>
                            </table>
                            </div>
                            
                            <p class="left">
                                Merci de votre visite chez Jacques et Déméter.
                                <br>A bientôt.
                                <br>Maxime
                            </p>
                        </td>
                        
                        {* Right sidebar *}
                        <td nowrap=nowrap class="sidebar">
                            <p class="sidebar-subtitle">BESOIN D'AIDE ?</p>
                            <hr>
                            <p>Nous sommes à votre écoute</p>
                            <p>
                                <img src="{image file="assets/img/icone-telephone.png" source="PaniersAbandonnes"}" alt="">
                                <br>
                                par téléphone<br>
                            </p>
    
                            <p>
                                <img src="{image file="assets/img/icone-email.png" source="PaniersAbandonnes"}" alt="">
                                <br>
                                par e-mail
                                <br>
                                contact@jacquesdemeter.fr
                            </p>
                            
                            &nbsp;<br>
                            <p class="sidebar-subtitle">ENVOIS & RETOURS<br>SATISFAIT OU REMBOURSÉ</p>
                            <hr>
                            <p>
                                <img src="{image file="assets/img/icone-cadeau.png" source="PaniersAbandonnes"}" alt="">
                                <br>
                                Envois gratuits en Europe<br>Retours oferts en France
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr>
            <td class="footer">
                <p class="subtitle">PAIEMENT EN LIGNE ET SECURISE</p>
                <p>VOTRE PAIEMENT SUR NOTRE BOUTIQUE EN LIGNE EST SECURISE PAR NOS PARTENAIRES :<br>PAYPAL ET MERC@NET DE LA BNP PARIBAS </p>
                
                <p><img alt="Paiement par Carte Bancaire et Paypal" src="{image file='assets/img/moyens-de-paiement.png' source='PaniersAbandonnes'}"></p>
            </td>
        </tr>
        <tr>
            <td style="text-align: center;">
                <p>
                    <a href="{navigate to="index"}">
                        <img alt="Jacques & Déméter"  src="{image file='assets/img/logo-jd.png' source='PaniersAbandonnes'}">
                    </a>
                </p>
            </td>
        </tr>
        <tr>
            <td class="head head-bottom"><p>&nbsp;</p></td>
        </tr>
    </table>
</body>
</html>
