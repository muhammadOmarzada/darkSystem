class MLChatbot {
  constructor() {
    this.isOpen = false;
    this.isTyping = false;
    this.init();
  }

  init() {
    this.bindEvents();
    this.loadChatHistory();
    this.addQuickReplies();
  }

  bindEvents() {
    document.getElementById("chatbot-toggle").addEventListener("click", () => {
      this.toggleChatbot();
    });

    document.getElementById("chatbot-close").addEventListener("click", () => {
      this.closeChatbot();
    });

    document.getElementById("chatbot-send").addEventListener("click", () => {
      this.sendMessage();
    });

    document
      .getElementById("chatbot-input")
      .addEventListener("keypress", (e) => {
        if (e.key === "Enter" && !e.shiftKey) {
          e.preventDefault();
          this.sendMessage();
        }
      });
  }

  toggleChatbot() {
    const container = document.getElementById("chatbot-container");
    if (this.isOpen) {
      container.style.display = "none";
      this.isOpen = false;
    } else {
      container.style.display = "flex";
      this.isOpen = true;
      document.getElementById("chatbot-input").focus();
    }
  }

  closeChatbot() {
    document.getElementById("chatbot-container").style.display = "none";
    this.isOpen = false;
  }

  async sendMessage(message = null) {
    const input = document.getElementById("chatbot-input");
    const messageText = message || input.value.trim();

    if (!messageText || this.isTyping) return;

    // Add user message
    this.addMessage(messageText, "user");
    input.value = "";

    // Hide quick replies after first message
    this.hideQuickReplies();

    // Show typing indicator
    this.showTypingIndicator();

    try {
      const response = await fetch("chatbot_api.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ message: messageText }),
      });

      const data = await response.json();

      // Hide typing indicator
      this.hideTypingIndicator();

      if (data.error) {
        this.addMessage("Maaf, terjadi kesalahan: " + data.error, "bot");
      } else {
        this.addFormattedMessage(data.response, "bot");

        // Add follow-up suggestions based on intent
        if (data.intent) {
          this.addFollowUpSuggestions(data.intent);
        }
      }
    } catch (error) {
      this.hideTypingIndicator();
      this.addMessage(
        "Maaf, terjadi kesalahan koneksi. Silakan coba lagi.",
        "bot"
      );
    }

    this.saveChatHistory();
  }

  addMessage(content, sender) {
    const messagesContainer = document.getElementById("chatbot-messages");
    const messageDiv = document.createElement("div");
    messageDiv.className = `message ${sender}-message`;

    const contentDiv = document.createElement("div");
    contentDiv.className = "message-content";
    contentDiv.textContent = content;

    messageDiv.appendChild(contentDiv);
    messagesContainer.appendChild(messageDiv);

    this.scrollToBottom();
  }

  addFormattedMessage(content, sender) {
    const messagesContainer = document.getElementById("chatbot-messages");
    const messageDiv = document.createElement("div");
    messageDiv.className = `message ${sender}-message`;

    const contentDiv = document.createElement("div");
    contentDiv.className = "message-content formatted-content";

    // Format the content
    let formattedContent = this.formatMessageContent(content);
    contentDiv.innerHTML = formattedContent;

    messageDiv.appendChild(contentDiv);
    messagesContainer.appendChild(messageDiv);

    this.scrollToBottom();
  }

  formatMessageContent(content) {
    // Convert line breaks to HTML
    content = content.replace(/\n/g, "<br>");

    // Convert **bold** to <strong>
    content = content.replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>");

    // Convert *italic* to <em>
    content = content.replace(/\*(.*?)\*/g, "<em>$1</em>");

    // Convert bullet points
    content = content.replace(/• /g, '<span class="bullet">•</span> ');

    // Add spacing after emojis
    content = content.replace(/([\u{1F300}-\u{1F9FF}])/gu, "$1 ");

    return content;
  }

  addQuickReplies() {
    const messagesContainer = document.getElementById("chatbot-messages");
    const quickRepliesDiv = document.createElement("div");
    quickRepliesDiv.className = "quick-replies";
    quickRepliesDiv.id = "quick-replies";

    const suggestions = [
      "📊 Tampilkan klasemen tim",
      "⚔️ Jadwal pertandingan",
      "📈 Statistik penjualan",
      "🏆 Tim peringkat 1",
      "❓ Apa yang bisa kamu lakukan?",
    ];

    suggestions.forEach((suggestion) => {
      const button = document.createElement("button");
      button.className = "quick-reply-btn";
      button.textContent = suggestion;
      button.addEventListener("click", () => {
        this.sendMessage(suggestion);
      });
      quickRepliesDiv.appendChild(button);
    });

    messagesContainer.appendChild(quickRepliesDiv);
    this.scrollToBottom();
  }

  hideQuickReplies() {
    const quickReplies = document.getElementById("quick-replies");
    if (quickReplies) {
      quickReplies.style.display = "none";
    }
  }

  addFollowUpSuggestions(intent) {
    const suggestions = {
      teams: ["🔍 Detail tim tertentu", "📊 Perbandingan tim"],
      matches: ["🎯 Pertandingan hari ini", "📅 Jadwal minggu ini"],
      statistics: ["💰 Revenue per minggu", "📈 Trend penjualan"],
    };

    if (suggestions[intent]) {
      const messagesContainer = document.getElementById("chatbot-messages");
      const suggestionsDiv = document.createElement("div");
      suggestionsDiv.className = "follow-up-suggestions";

      const title = document.createElement("div");
      title.className = "suggestions-title";
      title.textContent = "Pertanyaan lainnya:";
      suggestionsDiv.appendChild(title);

      suggestions[intent].forEach((suggestion) => {
        const button = document.createElement("button");
        button.className = "suggestion-btn";
        button.textContent = suggestion;
        button.addEventListener("click", () => {
          this.sendMessage(suggestion);
          suggestionsDiv.remove();
        });
        suggestionsDiv.appendChild(button);
      });

      messagesContainer.appendChild(suggestionsDiv);
      this.scrollToBottom();
    }
  }

  showTypingIndicator() {
    this.isTyping = true;
    const messagesContainer = document.getElementById("chatbot-messages");
    const typingDiv = document.createElement("div");
    typingDiv.className = "message bot-message";
    typingDiv.id = "typing-indicator";

    const typingContent = document.createElement("div");
    typingContent.className = "typing-indicator";
    typingContent.innerHTML = `
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        `;

    typingDiv.appendChild(typingContent);
    messagesContainer.appendChild(typingDiv);
    this.scrollToBottom();

    document.getElementById("chatbot-send").disabled = true;
  }

  hideTypingIndicator() {
    this.isTyping = false;
    const typingIndicator = document.getElementById("typing-indicator");
    if (typingIndicator) {
      typingIndicator.remove();
    }

    document.getElementById("chatbot-send").disabled = false;
  }

  scrollToBottom() {
    const messagesContainer = document.getElementById("chatbot-messages");
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }

  saveChatHistory() {
    const messages = document.getElementById("chatbot-messages").innerHTML;
    localStorage.setItem("ml_chatbot_history", messages);
  }

  loadChatHistory() {
    const history = localStorage.getItem("ml_chatbot_history");
    if (history) {
      document.getElementById("chatbot-messages").innerHTML = history;
      this.scrollToBottom();
    }
  }
}

// Initialize chatbot when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  new MLChatbot();
});
