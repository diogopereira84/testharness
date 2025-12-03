## Fedex_MarketplaceUi

---
It was created with the intent to have all the UI features that can be used across multiple modules from the Marketplace modules.

**Currently** it has only one feature, the **`Notification Messages`** or "Toast Messages". 

- It's currently been used by these modules: 

    >**Fedex_MarketplacePunchout**: For notify the customer about the Navitor maintenance.

    >**Fedex_Delivery**: For notify when the delivery method has changed within checkout. 

<br>

- Each module calls the function `addMessage()` from:
    > MarketplaceUi/view/frontend/web/js/view/manage_toast_messages.js 
    
    <br>
   

- It saves the message in the `localStorage`. 

- The file can be imported in another `.js` file or called within a `.phtml` file using the **JS Declarative Notation from Magento**. 
    
- To register the messages, use the function `addMessage( messageJSON )` and pass a JSON string with this format as a parameter:

    ```json
    {
        'type': 'warning',   
        'category': 'message_category', 
        'title': 'Message title',       
        'text': 'Message Content',      
    }
    ```
    - **`type`**:     Must be 'warning', 'success' or 'error'
    - **`category`**: Can be any string
    - **`title`**:    Can be any string
    - **`text`**:     Can be any string



<br>

### Please, **update this file**:
When you or your team `add new features` for the Fedex_MarketplaceUi

<br>

## **Contributors**
---

- [Rafael Orige de Vargas](rafael.vargas.osv@fedex.com)
- [Rafael Luiz Souza Silva](rafaelsilva.osv@fedex.com)
