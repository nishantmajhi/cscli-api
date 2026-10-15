/* Unit 8: Structure and Union
 * Array of structures - storing records for multiple students. */
#include <stdio.h>

struct Student {
    char name[50];
    int roll;
    float marks;
};

int main(void) {
    struct Student students[3] = {
        {"Aarav", 1, 87.5},
        {"Bina", 2, 92.0},
        {"Chetan", 3, 78.25}
    };

    printf("%-10s %-6s %-6s\n", "Name", "Roll", "Marks");
    for (int i = 0; i < 3; i++) {
        printf("%-10s %-6d %-6.2f\n",
               students[i].name, students[i].roll, students[i].marks);
    }

    return 0;
}
