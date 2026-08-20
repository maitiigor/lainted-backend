# Lainted — Master Product Document
### Version 3.0 — Full Features, User Stories, Anti-Leakage, Monetization, C2C & Category Expansion

---

## 🎯 PRODUCT VISION

> A hyperlocal, trust-first secondhand marketplace for Nigeria — starting with fashion, expanding to everything secondhand.

**Core Promise:**
- Buyers: Find affordable secondhand items from people near you, safely
- Sellers: Turn your old items into cash, reach buyers nearby, grow a business
- Platform: Every transaction protected, every contact private, every naira tracked

---

## 👤 USER ROLES

| Role | Description |
|------|-------------|
| **Buyer** | Browses and purchases items |
| **Personal Seller (C2C)** | Sells their own old clothes and household items |
| **Business Seller** | Professional bale trader or thrift reseller |
| **Both** | Most users will buy and sell simultaneously |
| **Admin** | Platform moderator and manager |

---

## 🗂️ CATEGORY ROADMAP

### V1 — Launch (Fashion)
```
Fashion & Clothing
├── Tops (t-shirts, shirts, blouses)
├── Bottoms (trousers, jeans, skirts)
├── Dresses & Gowns
├── Shoes & Footwear
├── Bags & Accessories
├── Kids & Baby Clothing
├── Jackets & Outerwear
├── Sportswear
├── Traditional & Ankara
├── Underwear & Lingerie
└── Bales & Bundles
```

### V2 — Growth (3-6 months after launch)
```
Baby & Kids
├── Baby furniture (cot, pram, high chair)
├── Toys & Games
└── School items & bags

Books & Education
├── Textbooks (primary, secondary, university)
├── Novels & Fiction
└── Educational materials
```

### V3 — Expansion (6-12 months)
```
Electronics & Phones
├── Smartphones & Tablets
├── Laptops & Computers
├── TVs & Decoders
├── Sound systems
└── Cameras

Small Appliances & Kitchen
├── Blenders, microwaves, kettles
├── Pots, cutlery, kitchen items
└── Fans, irons, small gadgets
```

### V4 — Scale (12+ months)
```
Household & Furniture
├── Sofas & Chairs
├── Beds & Mattresses
├── Wardrobes & Shelves
├── Dining sets & tables
└── Curtains & rugs

Large Appliances
├── Washing machines & dryers
├── Fridges & freezers
├── Air conditioners
├── Generators & inverters    ← major Nigeria category
└── Water dispensers

Sports & Fitness
├── Exercise equipment
├── Bicycles
└── Sports gear
```

---

## 🔐 1. AUTHENTICATION & ONBOARDING

### Features
- Phone number signup (OTP via SMS — no email required)
- Google sign-in option
- Profile setup (name, photo, LGA, area/landmark, bio)
- Seller type selection: Personal Seller or Business Seller
- Onboarding tutorial (skippable)
- Account verification badge (NIN-lite or BVN soft check)
- Seller agreement and anti-leakage policy acceptance on signup

### User Stories
- *As a new user, I want to sign up with my phone number so I don't need an email.*
- *As a user, I want to set my location by LGA and landmark so nearby items show first.*
- *As a personal seller, I want a simple setup so I can start listing my old clothes immediately.*
- *As a business seller, I want to set up my store properly so buyers take me seriously.*
- *As a returning user, I want to log in with OTP so I don't need to remember a password.*

---

## 🛍️ 2. LISTINGS & INVENTORY

### Features
- Create listing with up to 6 photos
- Auto-watermark all listing photos with app name
- Title, description, category, subcategory
- Item type tag: Fashion / Household / Electronics / Baby & Kids / Books
- Condition rating: New / Like New / Good / Fair
- Size field (for fashion items)
- Brand and model fields (for electronics and appliances)
- Serial number field (encrypted, for electronics verification)
- Weight/size tier (for delivery cost calculation)
- Price setting with negotiable toggle
- "Is functional" toggle for electronics and appliances
- "Requires installation" flag for large appliances
- Location tagging by LGA + landmark (no full address)
- Bale listing for bulk fashion lots
- Wardrobe Cleanout mode (discounted badge, featured placement)
- Bundle deal (buy any X items for ₦Y from same seller)
- Draft saving
- Edit / delete listing
- Mark as sold
- Boost listing (paid promotion)
- Listing expiry (auto-archive after 30 days)
- Repost expired listing in one tap
- Contact info auto-detection (blocks phone numbers, WhatsApp links, social handles)

### Seller Type Differences

| Feature | Personal Seller | Business Seller |
|---------|----------------|-----------------|
| Listing flow | Simple (fewer fields) | Full (all fields) |
| Max active listings | 20 (free) | Unlimited (Pro/Elite) |
| Storefront page | Basic profile | Full branded storefront |
| Analytics | None | Full dashboard |
| Subscription | No | Yes |
| Commission | 5-8% per sale | None (subscription covers) |
| Wardrobe section | Yes | No |
| Bale listings | No | Yes |

### User Stories
- *As a personal seller, I want a simple listing flow so I can list my old dress in under 2 minutes.*
- *As a business seller, I want to list a full bale as a bundle so I can sell in bulk.*
- *As a seller, I want my photos watermarked automatically so my items aren't stolen.*
- *As a personal seller, I want a Wardrobe Cleanout mode so buyers know I'm selling everything fast.*
- *As a seller listing a phone, I want to add the model and condition so buyers know exactly what they're getting.*
- *As an admin, I want contact details auto-blocked so sellers can't redirect buyers off-platform.*

---

## 🔍 3. DISCOVERY & SEARCH

### Features
- Home feed sorted by proximity (LGA first)
- Category and item type filtering
- Keyword search
- Filter by: category, item type, size (fashion), brand, price range, condition, location, seller type
- Sort by: newest, price low–high, distance, most popular
- Trending items section
- Recently viewed
- Saved / wishlist items
- New drop notifications from followed sellers
- Wardrobe browse ("Shop [Name]'s Wardrobe")
- Seller storefront discoverable only inside the app
- "Fresh Finds" — new Wardrobe Cleanout listings
- "Near You" — items within buyer's LGA

### User Stories
- *As a buyer, I want to see items near me first so pickup is convenient.*
- *As a buyer, I want to filter by category so I find clothes separately from electronics.*
- *As a buyer, I want to browse a specific seller's full wardrobe.*
- *As a buyer, I want to filter by condition so I only see "Like New" items.*
- *As a buyer looking for a generator, I want to filter by brand so I find a Thermocool specifically.*

---

## 💬 4. IN-APP CHAT

### Features
- Anonymous in-app messaging (no phone numbers ever revealed)
- Image sharing in chat (buyers can request more photos)
- Contact detail blocker — auto-detects and redacts phone numbers, WhatsApp links, Telegram, Instagram, email
- Warning shown when blocked content detected, strike recorded
- Quick reply templates ("Is this available?", "What's your last price?", "I'll take it", "Can you do better?")
- Chat notifications (push + in-app)
- Block / report user from chat
- Chat history preserved permanently for dispute resolution
- No WhatsApp redirect — all transactions must complete in-app
- Typing indicators and read receipts
- Chat initiated from listing page — one tap to open

### User Stories
- *As a buyer, I want to ask the seller for more photos of an item before committing.*
- *As a buyer, I want quick reply options so I can message sellers without typing much.*
- *As a buyer, I want all chat history saved in case a dispute comes up.*
- *As an admin, I want contact leak attempts flagged automatically and recorded as strikes.*

---

## 🤝 5. NEGOTIATION ENGINE

This is a core business mechanism — not just a feature. Haggling is deeply embedded in Nigerian market culture. The negotiation engine brings the Balogun Market experience into the app.

### How It Works — Full Flow

```
BUYER sees listing at ₦5,000 (negotiable)
     ↓
BUYER taps "Make Offer" → enters ₦3,500
     ↓
SELLER receives offer notification instantly
     ↓
SELLER has 3 options:
  ├── Accept  → price locked at ₦3,500, proceed to checkout
  ├── Counter → enters ₦4,200, sends back to buyer
  └── Decline → buyer can try again or walk away
     ↓
IF COUNTERED → BUYER has 3 options:
  ├── Accept counter → locked at ₦4,200, proceed to checkout
  ├── Counter again  → enters ₦3,800, back to seller
  └── Decline        → negotiation ends
     ↓
Rounds continue until:
  ├── Agreement reached → checkout unlocked at agreed price
  ├── Either party declines → conversation stays open for new offer
  └── Offer expires (24 hours) → auto-cancelled, new offer needed
```

### Features

**Offer System**
- "Make Offer" button on every negotiable listing
- Buyer enters custom price — cannot exceed listed price
- Offer displayed as a special message card in chat
- Both parties see full offer history in conversation
- Offer expires automatically after 24 hours if no response
- Seller can set minimum acceptable price (private — never shown to buyer)
- If buyer's offer meets or exceeds seller's minimum → auto-accept option

**Counter Offer**
- Seller can counter with any price between buyer's offer and listed price
- Buyer can counter back — up to 5 rounds maximum
- Each counter resets the 24-hour expiry clock
- Counter price shown clearly as a new offer card in chat
- Both parties see the full negotiation trail

**Offer Notifications**
- Instant push notification to seller when offer received
- "You have an offer — respond within 24 hours" urgency message
- Buyer notified instantly when seller accepts, counters, or declines
- Reminder notification at 6 hours and 2 hours before expiry

**Agreed Price Lock**
- Once both parties agree — price is locked
- Checkout button activates at agreed price
- Neither party can change price after agreement
- Agreement card shown in chat: "You agreed on ₦4,200 — pay now"
- Checkout must happen within 2 hours of agreement or it expires

**Fixed Price Listings**
- Sellers can mark items as fixed price (non-negotiable)
- "Make Offer" button hidden on fixed price listings
- "Buy Now" replaces it — direct to checkout at listed price
- Useful for business sellers who don't want negotiation overhead

**Bulk / Bale Negotiation**
- Bale listings support bundle price negotiation
- Buyer can negotiate total bale price
- Seller can offer "buy 2 items, get discount" deals
- Group buy price negotiated collectively by organiser

**Negotiation Etiquette Guards**
- Lowball detection — offer below 30% of listed price triggers warning to buyer
- "This offer may be too low — sellers typically accept offers above X"
- Prevents time-wasting for sellers
- Repeated lowball offers from same buyer flagged to admin

**Seller Controls**
- Set negotiable toggle per listing (on/off)
- Set private minimum price (never revealed to buyer)
- Auto-accept offers at or above minimum price
- See all active offers across all listings in seller dashboard
- Bulk respond to multiple offers at once (Pro/Elite sellers)

**Buyer Controls**
- See all active offers they've made across all listings
- Retract an offer before seller responds
- Set offer expiry preference (12hr / 24hr)
- Offer history visible in their profile

**Platform Revenue from Negotiation**
- Protection fee calculated on FINAL agreed price, not listed price
- Encourages honest negotiation — platform earns on real transaction value
- No fee on failed negotiations — only on completed sales

### Negotiation States

| State | Description |
|-------|-------------|
| `pending` | Offer sent, waiting for seller response |
| `countered` | Seller sent back a counter price |
| `accepted` | Both parties agreed on price |
| `declined` | One party rejected, no deal |
| `expired` | 24 hours passed with no response |
| `retracted` | Buyer pulled offer before response |
| `checkout_expired` | Agreed but buyer didn't pay within 2 hours |
| `completed` | Payment made at agreed price |

### User Stories
- *As a buyer, I want to make an offer below the listed price so I can get a better deal.*
- *As a seller, I want to counter an offer so I don't just accept the first low price.*
- *As a seller, I want to set a private minimum price so the app auto-accepts anything above it.*
- *As a buyer, I want to see the full negotiation history in chat so I know where we stand.*
- *As a seller, I want instant notification when I receive an offer so I don't miss it.*
- *As a buyer, I want the agreed price locked in so the seller can't change it after we agreed.*
- *As a seller, I want to bulk respond to offers across my listings from one screen.*
- *As a buyer making an offer on a ₦50,000 washing machine, I want 5 rounds of counter-offer so we have room to negotiate properly.*
- *As a seller, I want a warning shown to buyers making unreasonably low offers so I don't waste time.*
- *As a platform, I want negotiation to stay in-app so I capture the agreed price for escrow.*
- *As a buyer in a group buy, I want our organiser to negotiate the bale price on our behalf.*

---

## 🏷️ 6. ITEM GRADING SYSTEM

Grading is a trust mechanism. It sets buyer expectations before negotiation begins, reduces disputes after delivery, and gives sellers a way to justify their price. A buyer negotiating on a Grade A item negotiates differently than on a Grade C item — and that's exactly right.

---

### Why Generic Condition Labels Fail

```
"Good condition" means nothing in Nigeria
├── Seller thinks: minor tear but still wearable = good
├── Buyer thinks: no visible flaws at all = good
└── Result: dispute, refund, bad review, platform loses trust
```

You need a **standardised, visual, Nigerian-market-specific grading system** — not just words.

---

### Fashion & Clothing Grades

| Grade | Label | Nigerian Equivalent | Description |
|-------|-------|---------------------|-------------|
| **A++** | Brand New with Tag | "NT" / "Never Touched" | Unworn, original tags attached, no flaws whatsoever |
| **A+** | Brand New No Tag | "New No Tag" | Unworn, no tags but no flaws — may have been tried on |
| **A** | Excellent | "Grade A" / "First Selection" | Worn 1–3 times, looks brand new, no visible wear |
| **B** | Very Good | "Grade B" | Light use, minor wash fade acceptable, no damage |
| **C** | Good | "Grade C" | Noticeable wear, may have small fading or pilling, fully wearable |
| **D** | Fair | "Grade D" / "Church Lainted" | Heavy wear, visible flaws (fading, light stain, minor tear), priced low |
| **X** | Damaged / As-Is | "Repair Grade" | Has damage — hole, stain, broken zip — sold for parts or repair |

**Grade must be selected per item — not optional.**

---

### Electronics & Appliances Grades

| Grade | Label | Description |
|-------|-------|-------------|
| **A++** | Sealed / Brand New | Original packaging, never opened |
| **A+** | Open Box | Opened but never used, all accessories present |
| **A** | Excellent Working | Used, no scratches, fully functional, all parts present |
| **B** | Good Working | Minor cosmetic marks, fully functional |
| **C** | Fair Working | Visible wear/scratches, works but may have minor issue (e.g. battery drains faster) |
| **D** | Functional with Faults | Works but has known fault — must be clearly disclosed |
| **X** | For Parts / Not Working | Does not power on or is incomplete — sold as-is |

**Grade D and X listings require mandatory fault disclosure in a dedicated field.**

---

### Household & Furniture Grades

| Grade | Label | Description |
|-------|-------|-------------|
| **A** | Excellent | Like new, no marks, complete |
| **B** | Good | Minor scuffs or wear, fully functional |
| **C** | Fair | Visible wear, may need minor repair, still usable |
| **D** | Poor | Significant wear, needs repair — price should reflect |
| **X** | For Salvage | Broken or incomplete — buyer takes as-is |

---

### Grading Rules & Enforcement

**Mandatory grading:**
- Every listing must have a grade selected before publishing
- Cannot list without choosing a grade — no skipping

**Photo requirements per grade:**
| Grade | Minimum Photos Required |
|-------|------------------------|
| A++ / A+ | 2 photos (front + tag/packaging) |
| A / B | 3 photos (front, back, detail) |
| C | 4 photos (must show wear areas clearly) |
| D | 5 photos (all flaws must be photographed) |
| X | 5 photos (damage clearly documented) |

**Grade mismatch = automatic dispute ground:**
- If delivered item is lower grade than listed — buyer gets automatic refund eligibility
- No questions asked for grade disputes on first offence
- Repeated grade mismatches trigger seller strike

**Buyer filter by grade:**
- Buyers can filter search results by minimum grade
- "Show me only Grade A and above" for quality hunters
- "Show me Grade C and below" for budget buyers
- Grade shown as prominent badge on every listing card

---

### How Grading Connects to Negotiation

```
Grade A item listed at ₦5,000
→ Buyer knows quality is high
→ Negotiation room is smaller (maybe ₦4,000–₦4,500 is reasonable)
→ Lowball threshold adjusted: 40% floor for Grade A vs 50% for Grade C

Grade D item listed at ₦1,500
→ Buyer knows it has visible flaws
→ Price expectation is already low
→ More room to negotiate downward
→ Seller already priced for condition
```

The grade anchors the negotiation. Both parties enter with calibrated expectations.

**Grade-aware lowball detection:**
```
Grade A++ item:  offer below 60% of listed = lowball warning
Grade A item:    offer below 50% of listed = lowball warning
Grade B item:    offer below 40% of listed = lowball warning
Grade C item:    offer below 30% of listed = lowball warning
Grade D / X:     no lowball floor — anything goes
```

---

### Bale / Bundle Grading

Bales are graded differently — they contain mixed items:

| Bale Grade | Description |
|------------|-------------|
| **Premium Bale** | Majority Grade A/B items, curated selection |
| **Mixed Bale** | Mix of all grades, typical bend-down select bale |
| **Economy Bale** | Majority Grade C/D, budget pricing |
| **Sorted Bale** | Specific category sorted (e.g. all shirts, all children's) |

Bale seller must also state:
- Approximate item count
- Dominant category
- Country of origin (UK grades, US grades, China grades — different quality expectations)

---

### Grade Verification (V2 Feature)

For high-value items (electronics > ₦30,000):
- Seller uploads video showing item powered on and functional
- Platform reviews within 2 hours before listing goes live
- "Platform Verified" badge added to listing
- Gives buyers extra confidence on expensive purchases

---

### Grading in Search & Discovery

```
Listing card shows:
┌─────────────────────────┐
│ [Photo]                 │
│ Nike Air Force 1        │
│ ┌──────┐                │
│ │GRADE │ ₦4,500         │
│ │  A   │ Negotiable     │
│ └──────┘ Surulere, Lagos│
└─────────────────────────┘
```

Grade badge is colour-coded:
- A++ / A+ → Green
- A / B → Blue  
- C → Orange
- D / X → Red

---

### User Stories
- *As a buyer, I want to see the grade clearly on every listing so I know what condition to expect.*
- *As a buyer, I want to filter by Grade A and above so I only see quality items.*
- *As a seller, I want to show my item is Grade A++ so I can justify my price in negotiation.*
- *As a buyer negotiating, I want the grade to anchor the price discussion so we both start from the same understanding.*
- *As a seller listing a Grade D item, I want clear instructions on what photos to take so buyers can't dispute later.*
- *As a buyer who received a Grade C item listed as Grade A, I want automatic refund eligibility.*
- *As an admin, I want grade mismatch disputes flagged automatically so I can review seller behaviour.*
- *As a buyer browsing bales, I want to know if it's a Premium or Economy bale so I know what price to expect.*
- *As a seller, I want the platform to tell me the minimum photos required for my chosen grade so I list correctly.*

---

## 🚚 7. ORDERS & DELIVERY

### Features
- Pickup option (buyer comes to seller's general area)
- Local delivery — standard (clothes, small items — okada/bike rider)
- Bulky delivery — medium items (electronics, small appliances — car)
- Furniture delivery — large items (sofas, washing machines — van/truck)
- Third-party logistics integration: GIG Logistics, Kwik, Sendbox
- Masked address delivery — seller never sees buyer's full address
- Only logistics rider receives decrypted delivery address
- Order summary before confirming
- Order status: Pending → Confirmed → Prepared → In Transit → Delivered → Completed
- Buyer confirms receipt to release escrow
- Auto-complete after 48 hours if buyer doesn't dispute
- Dispute window (48 hours after delivery)
- Installation booking (for appliances that require it)
- Order history for both parties

### Delivery Tiers

| Tier | Items | Provider | Est. Cost |
|------|-------|----------|-----------|
| Standard | Clothes, books, small items | Bike rider | ₦500–₦1,500 |
| Bulky | Electronics, small appliances | Car | ₦2,000–₦8,000 |
| Furniture | Sofas, washing machines, fridges | Van/truck | ₦8,000–₦25,000 |

### User Stories
- *As a buyer, I want to choose my delivery tier based on item size.*
- *As a buyer purchasing a fridge, I want delivery by van so it arrives safely.*
- *As a buyer, I want my home address hidden from the seller.*
- *As a buyer, I want a 48-hour window to raise a dispute if the item is different from the listing.*
- *As a seller of a washing machine, I want to offer installation as an add-on service.*

---

## 💳 8. PAYMENTS, WALLET & ESCROW

### Features
- Paystack integration (card, bank transfer, USSD)
- In-app wallet (deposit, spend, withdraw to bank)
- Escrow system — payment held until buyer confirms receipt
- Auto-release after 48 hours if no dispute
- Wallet balance shown on dashboard
- Loyalty points earned on every in-app transaction
- Loyalty points spendable in-app only (not withdrawable)
- Split payment for group bale buys
- Full transaction history
- Withdrawal to Nigerian bank account
- Cash on delivery for verified sellers with 4.5+ rating only
- Buyer protection policy — refund if item significantly not as described
- Clear messaging: "Payments outside the app are not protected"
- Higher escrow verification for large items (electronics, appliances > ₦50,000)

### User Stories
- *As a buyer, I want to pay via bank transfer so I don't need a card.*
- *As a buyer spending ₦80,000 on a generator, I want escrow so my money is protected.*
- *As a seller, I want fast withdrawal after the buyer confirms delivery.*
- *As a buyer, I want full refund protection if the phone I received is different from the listing.*
- *As a group, I want to split a bale cost through the app.*

---

## ⭐ 9. TRUST, RATINGS & REPUTATION

### Features
- Star rating after each transaction (1–5)
- Written review (buyer reviews seller, seller reviews buyer)
- Reputation entirely locked to platform — non-transferable, non-exportable
- Verified seller badge (NIN soft check)
- Electronics/appliance sellers require stricter verification for high-value items
- Response rate indicator
- Total in-app sales count
- Trust score on profile
- Seller rank: New → Rising → Trusted → Top → Elite
- Report listing, user, or message
- Strike system (3 strikes = suspension, 5 = permanent ban)
- Banned sellers forfeit wallet balance and lose all ratings permanently
- Off-platform payment reporting rewarded with loyalty points

### User Stories
- *As a buyer, I want to see a seller's rating and total sales before buying a ₦60,000 fridge.*
- *As a seller, I understand my reputation lives entirely on this platform.*
- *As a buyer, I want to report a seller who asked me to pay on WhatsApp.*
- *As a buyer, I want to earn loyalty points for reporting off-platform attempts.*

---

## 👗 10. WARDROBE FEATURE (C2C Personal Selling)

### Features
- Every user has a "My Wardrobe" section on their profile
- List personal old clothes directly from wardrobe
- Followers can browse anyone's wardrobe
- "Shop [Name]'s Wardrobe" public-facing profile section
- Wardrobe Cleanout mode — all items discounted, special badge
- Bundle deal — "Any 3 items for ₦X"
- Wardrobe stats (items listed, items sold, total earned)
- Money earned from wardrobe sales goes to in-app wallet
- Wallet balance used to buy other items — money circulates in ecosystem
- Simple listing flow (3 steps vs full seller flow)

### C2C Growth Loop
```
User downloads app to buy
→ Sees "Sell your old clothes free" prompt after first purchase
→ Lists items from wardrobe (simple flow, 2 minutes)
→ Item sells → money lands in wallet
→ Uses wallet balance to buy more items
→ Tells friends "I just sold my dress on this app"
→ Friends download → buy → list their own wardrobes
→ Repeat
```

### User Stories
- *As a buyer who just received my order, I want to be prompted to list my own old clothes.*
- *As a personal seller, I want a 3-step listing flow so I can list my old jeans in 2 minutes.*
- *As a personal seller, I want to activate Wardrobe Cleanout so buyers know I want to sell fast.*
- *As a buyer, I want to browse a specific person's wardrobe like browsing their Instagram.*
- *As a personal seller, I want money from my sales to sit in my wallet so I can buy something else immediately.*

---

## 👥 11. SOCIAL & COMMUNITY FEATURES

### Features
- Follow sellers and personal wardrobes
- Seller storefront (business sellers — branded page, all listings, reviews)
- Custom storefront URL — opens only inside app
- Share listing to WhatsApp / Instagram — link requires app to complete purchase
- Group buy (split bale cost — in-app only)
- Thrift circle (invite friends to browse together)
- Flash sales (limited time drops — exclusive in-app)
- Trending hashtags (#LagosBales #AbujaThrift #IkejaSells)
- Referral program (invite friends, earn wallet credit)
- Community feed (thrift tips, styling ideas, finds of the week)
- "Someone near you just listed this" social proof nudges

### User Stories
- *As a buyer, I want to follow sellers so I see their new drops first.*
- *As a business seller, I want my storefront link to share on Instagram.*
- *As a seller, I want to run a flash sale to move old stock fast.*
- *As a group of friends, I want to split a bale cost through the app.*
- *As a user, I want to refer friends and earn wallet credit.*

---

## 🔔 12. NOTIFICATIONS

### Features
- Push notifications (new message, offer, order update, payment received)
- Price drop alert on wishlisted items
- New drop alerts from followed sellers
- Flash sale countdown alerts
- "Someone saved your listing" nudge to seller
- Weekly digest ("New items near you this week")
- In-app notification centre
- Notification preferences (on/off per type)

---

## 🧑‍💼 13. SELLER TOOLS & DASHBOARD (Business Sellers)

### Features
- Sales dashboard (revenue, orders, views, conversion)
- Listing performance per item
- Payout history and pending escrow balance
- Inventory manager (active, sold, draft, expired)
- Bulk listing tool
- Promo tools (discount codes, flash sales, bundle deals)
- Seller rank tracker
- Analytics: best-selling categories, peak times, repeat buyers
- Subscription management

### User Stories
- *As a business seller, I want to see which items get the most views.*
- *As a business seller, I want to run a discount code campaign.*
- *As a business seller, I want to bulk upload a full bale inventory.*

---

## 🛡️ 14. ANTI-LEAKAGE & PLATFORM PROTECTION

### Architecture (Zero Contact Revelation)
- Phone numbers never shown to either party — ever
- Auto-detection engine scans all chat and listing text
- Blocks and warns on: phone numbers, WhatsApp links, Telegram, Instagram handles, email addresses
- Strike recorded every time contact leak is attempted
- Masked delivery — seller never sees buyer's address
- Only logistics rider receives decrypted address
- All listing photos auto-watermarked
- Shared links open inside app — require login to view or buy
- Escrow as deterrent — off-platform payment = zero protection
- Reputation as deterrent — ratings, followers, rank tied to account
- Loyalty points and wallet balance non-withdrawable (spendable in-app only)
- Subscription sunk cost — pro sellers already invested monthly
- Off-platform reporting rewarded with loyalty points
- ToS enforcement: warning → 7-day suspension → permanent ban + wallet forfeiture

### User Stories
- *As a buyer, I know my payment is only protected when I pay through the app.*
- *As a seller, I understand my 4.9 rating and 400 followers exist only here.*
- *As a buyer, I want to report a seller who asked me to pay outside the app.*
- *As an admin, I want automated contact detection so I don't review every message manually.*

---

## 🛡️ 15. ADMIN & MODERATION (Filament Panel)

### Features
- User management (verify, warn, suspend, ban)
- Listing moderation (review flagged, remove violations)
- Dispute resolution centre (full chat + order history visible)
- Contact leak attempt log and analytics
- Platform analytics (GMV, DAU, top locations, top sellers, category breakdown)
- Fraud detection flags
- Promoted listing management
- Strike tracking per user
- Category and subcategory management
- Subscription and revenue dashboard
- Platform-wide announcements

---

## ⚙️ 16. SETTINGS & ACCOUNT

### Features
- Edit profile (name, photo, area, bio)
- Change phone number (OTP verification)
- Notification preferences
- Privacy settings
- Blocked users list
- Delete account (30-day recovery window, wallet forfeited after)
- Dark mode
- Language toggle (English / Pidgin)
- Help & FAQ
- In-app support only

---

## 💰 17. MONETIZATION MODEL

### Revenue Streams

#### Stream 1 — Business Seller Subscriptions (Primary)
| Plan | Price | Listings | Key Features |
|------|-------|----------|--------------|
| Free | ₦0 | 10 active | Basic analytics, standard rank |
| Pro | ₦1,500/mo | Unlimited | Full analytics, priority rank, flash sales, discount codes |
| Elite | ₦3,500/mo | Unlimited | Everything Pro + featured storefront, bulk listing, dedicated support |

#### Stream 2 — Personal Seller Commission (C2C)
- 5–8% commission per completed sale
- No subscription — casual sellers pay only when they earn
- Free to list always

#### Stream 3 — Buyer Protection Fee
- 1.5% added to buyer's payment on all transactions
- Framed as "protection fee" not platform fee
- Covers escrow, dispute resolution, refund guarantee
- Example: ₦5,000 item → buyer pays ₦5,075

#### Stream 4 — Promoted Listings
- ₦200–₦500 per boost (3–7 days)
- Available to all seller types

#### Stream 5 — Logistics Partnership Commission
- Partner with GIG, Kwik, Sendbox
- Earn referral cut per delivery booked through app
- No cost to buyer or seller

#### Stream 6 — Featured Seller Slots
- Homepage and category page placement
- Weekly auction or fixed price
- Elite plan sellers only

#### Stream 7 — Group Buy Facilitation Fee (V3)
- 2–3% of total bale value for coordinating group buys
- Platform handles split payments and trust

### Conservative Year 1 Projection

| Stream | Assumption | Monthly |
|--------|------------|---------|
| Pro subscriptions | 200 sellers × ₦1,500 | ₦300,000 |
| Elite subscriptions | 30 sellers × ₦3,500 | ₦105,000 |
| C2C commissions | ₦500k GMV × 6% | ₦30,000 |
| Protection fees | ₦2M GMV × 1.5% | ₦30,000 |
| Promoted listings | 100 boosts × ₦300 | ₦30,000 |
| Logistics commission | 500 deliveries × ₦100 | ₦50,000 |
| **Total** | | **~₦545,000/mo** |

*1,000 Pro sellers = ₦1.5M/month from subscriptions alone*

---

## 🗺️ 18. MVP PRIORITY MAP

| Phase | Timeline | Focus |
|-------|----------|-------|
| **V1 — Launch** | Month 1-2 | Auth, Fashion listings, Search, Chat, Pickup, Paystack escrow, Basic ratings, Wardrobe C2C |
| **V2 — Growth** | Month 3-5 | Wallet, Seller dashboard, Subscriptions, Follow/Storefront, Notifications, Baby & Kids, Books |
| **V3 — Expansion** | Month 6-9 | Electronics, Small appliances, Group buy, Flash sales, Logistics tiers, Bulk listing |
| **V4 — Scale** | Month 10+ | Large appliances, Furniture, Generators, Web preview pages, Flutter Web marketplace |

---

## 📐 19. CORE DESIGN PRINCIPLES

1. **Mobile-first** — optimized for low-end Android, data-light
2. **Zero contact leakage** — by architecture, not just policy
3. **Trust by default** — escrow, ratings, verification from day one
4. **Nigerian context** — Pidgin, landmarks, bank transfer, haggling respected
5. **Seller-first growth** — supply drives demand
6. **Progressive expansion** — fashion first, everything secondhand eventually
7. **Platform stickiness** — every feature increases the cost of leaving
8. **Circular economy** — money earned from selling gets spent on buying

---

## 🔧 20. TECH STACK

```
Mobile:       Flutter (Riverpod, Dio, GoRouter)
Backend:      Laravel 11 (Sanctum, Reverb WebSockets, Queues)
Database:     MySQL + Redis
Payments:     Paystack
SMS/OTP:      Termii (Nigerian provider)
Images:       Cloudinary (with watermarking)
Push:         Firebase Cloud Messaging
Maps:         OpenStreetMap (free, geolocator package)
Admin:        Filament (Laravel)
Hosting:      Hetzner VPS (~$5-10/month)
Web (V4):     Flutter Web (same codebase)
```

---

*Master Document v3.0 — Lainted*
*Fashion → Wardrobe → Household → Everything Secondhand*
