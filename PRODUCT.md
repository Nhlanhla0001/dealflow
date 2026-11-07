# DealFlow AI — Product Context

## What it is

DealFlow AI is a negotiation and settlement workspace for freelance and agency deals. A buyer sets a budget and delivery window, a seller sets an opening offer and a private floor, and two AI agents negotiate the terms. Both humans approve the resulting agreement before PayPal checkout unlocks.

## Who it is for

- Founders and small teams buying project work.
- Freelancers and agencies selling project work.
- Anyone who wants an agent to do the back-and-forth without handing it the cheque book.

## The promise

**AI negotiates. Deterministic limits enforce. Humans approve. PayPal settles.**

The AI is allowed to propose prices, delivery windows, and reasoning. It is not allowed to approve terms, reveal the seller's private floor, exceed the buyer's budget, or move money.

## What makes it different

- The negotiation is a real agent-to-agent exchange, not a scripted animation.
- The safety boundary is visible: budget, floor, approvals, audit log.
- The payment step is a real PayPal sandbox order, capture, and signed webhook — not a mock checkout.
- The human approval gate is the product, not a compliance afterthought.

## Demo truth

- Seeded users: `buyer@dealflow.test` and `seller@dealflow.test`, password `password`.
- Demo transactions show draft, negotiated, awaiting approval, approved, and paid states.
- Sandbox only. No real money moves.
- AI: Gemini 3 Flash through Laravel AI SDK.
- Payments: PayPal Orders API v2, capture, and webhook signature verification.

## Voice

Direct, precise, quietly confident. Short sentences. Concrete numbers. No hype, no "revolutionary", no fake testimonials, no invented customer logos.

## Visual direction

Extend the existing DealFlow product language: white and ink, fine rules, generous space, data set in tabular numerals, one green signal for verified/approved. The landing page should read like a clean deal room, not a generic AI SaaS template.
