/* Unit 5: Control Statement
 * Looping with do-while (executes the body at least once). A simple
 * menu-driven program. */
#include <stdio.h>

int main(void) {
    int choice;

    do {
        printf("\n--- Menu ---\n");
        printf("1. Say Hello\n");
        printf("2. Exit\n");
        printf("Enter choice: ");
        scanf("%d", &choice);

        if (choice == 1) {
            printf("Hello!\n");
        }
    } while (choice != 2);

    printf("Goodbye!\n");
    return 0;
}
